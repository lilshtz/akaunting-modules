<?php

namespace Modules\BankFeeds\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\BankFeeds\Models\Transaction;
use Modules\DoubleEntry\Models\Account;
use Modules\DoubleEntry\Models\AccountDefault;
use Modules\DoubleEntry\Models\Journal;
use Modules\DoubleEntry\Models\JournalLine;
use RuntimeException;

class TransactionMatcher
{
    public function findMatches(Transaction $transaction): array
    {
        $amount = abs((float) $transaction->amount);
        $companyId = (int) $transaction->company_id;
        $date = $transaction->date?->copy();
        $prefix = DB::getTablePrefix();

        if (! $date) {
            return [];
        }

        $candidates = DB::table('double_entry_journal_lines as lines')
            ->join('double_entry_journals as journals', 'journals.id', '=', 'lines.journal_id')
            ->leftJoin('double_entry_accounts as accounts', 'accounts.id', '=', 'lines.account_id')
            ->where('journals.company_id', $companyId)
            ->where('journals.status', 'posted')
            ->whereNull('journals.deleted_at')
            ->whereRaw('ABS(ABS(' . $prefix . 'lines.debit - ' . $prefix . 'lines.credit) - ?) <= 0.01', [$amount])
            ->whereBetween('journals.date', [
                $date->copy()->subDays(3)->toDateString(),
                $date->copy()->addDays(3)->toDateString(),
            ])
            ->whereNotExists(function ($query) use ($transaction): void {
                $query->select(DB::raw(1))
                    ->from('bank_feed_transactions as transactions')
                    ->whereColumn('transactions.matched_journal_id', 'journals.id')
                    ->where('transactions.company_id', $transaction->company_id)
                    ->whereNull('transactions.deleted_at')
                    ->when($transaction->exists, fn ($inner) => $inner->where('transactions.id', '!=', $transaction->id));
            })
            ->select([
                'lines.id as journal_line_id',
                'lines.journal_id',
                'lines.account_id',
                'lines.debit',
                'lines.credit',
                'lines.description as line_description',
                'accounts.name as account_name',
            ])
            ->get();

        if ($candidates->isEmpty()) {
            return [];
        }

        $journalIds = $candidates->pluck('journal_id')->unique()->values();
        $lineIds = $candidates->pluck('journal_line_id')->unique()->values();

        $journals = Journal::query()
            ->byCompany($companyId)
            ->with('lines.account')
            ->whereIn('id', $journalIds)
            ->get()
            ->keyBy('id');

        $lines = JournalLine::query()
            ->whereIn('id', $lineIds)
            ->get()
            ->keyBy('id');

        $matches = $candidates->map(function ($candidate) use ($transaction, $amount, $date, $journals, $lines): ?array {
            $journal = $journals->get($candidate->journal_id);
            $line = $lines->get($candidate->journal_line_id);

            if (! $journal || ! $line) {
                return null;
            }

            $lineAmount = abs((float) $candidate->debit - (float) $candidate->credit);
            $score = 0;

            if (abs($lineAmount - $amount) <= 0.01) {
                $score += 50;
            }

            $dayDifference = abs($journal->date->diffInDays($date));

            if ($dayDifference === 0) {
                $score += 30;
            } elseif ($dayDifference <= 1) {
                $score += 20;
            } elseif ($dayDifference <= 3) {
                $score += 10;
            }

            if ($this->descriptionSimilarity($transaction->description, $journal->description, $candidate->account_name)) {
                $score += 20;
            }

            return [
                'journal_id' => $journal->id,
                'journal_line_id' => $candidate->journal_line_id,
                'score' => $score,
                'journal' => $journal,
                'line' => $line,
                'high_confidence' => $score > 80,
            ];
        })
            ->filter()
            ->sortByDesc('score')
            ->values()
            ->take(3)
            ->all();

        return $matches;
    }

    public function autoMatchAll(int $companyId): int
    {
        $count = 0;

        $transactions = Transaction::query()
            ->byCompany($companyId)
            ->whereIn('status', ['pending', 'categorized'])
            ->whereNull('matched_journal_id')
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        foreach ($transactions as $transaction) {
            $matches = $this->findMatches($transaction);
            $topMatch = $matches[0] ?? null;

            if (! $topMatch || $topMatch['score'] <= 80) {
                continue;
            }

            $this->acceptMatch($transaction->id, (int) $topMatch['journal_id']);
            $count++;
        }

        return $count;
    }

    public function acceptMatch(int $transactionId, int $journalId): Transaction
    {
        return DB::transaction(function () use ($transactionId, $journalId): Transaction {
            $transaction = Transaction::withoutGlobalScopes()->findOrFail($transactionId);
            $journal = Journal::query()
                ->byCompany($transaction->company_id)
                ->where('status', 'posted')
                ->findOrFail($journalId);

            $alreadyMatched = Transaction::query()
                ->byCompany($transaction->company_id)
                ->where('matched_journal_id', $journal->id)
                ->where('id', '!=', $transaction->id)
                ->exists();

            if ($alreadyMatched) {
                throw new RuntimeException('This journal entry is already matched to another bank transaction.');
            }

            $transaction->update([
                'matched_journal_id' => $journal->id,
                'status' => 'matched',
            ]);

            return $transaction->fresh(['matchedJournal']);
        });
    }

    public function rejectMatch(int $transactionId): Transaction
    {
        $transaction = Transaction::withoutGlobalScopes()->findOrFail($transactionId);

        $transaction->update([
            'matched_journal_id' => null,
            'status' => $transaction->category_id ? 'categorized' : 'pending',
        ]);

        return $transaction->fresh();
    }

    public function createJournalFromTransaction(Transaction $transaction, int $accountId): Journal
    {
        return DB::transaction(function () use ($transaction, $accountId): Journal {
            $offsetAccount = Account::query()
                ->byCompany($transaction->company_id)
                ->where('enabled', true)
                ->findOrFail($accountId);

            $bankAccountId = $this->resolveBankAccountId($transaction);
            $amount = abs((float) $transaction->amount);

            $journal = Journal::create([
                'company_id' => $transaction->company_id,
                'date' => $transaction->date,
                'reference' => Journal::generateNextReference($transaction->company_id),
                'description' => $transaction->description,
                'basis' => 'cash',
                'status' => 'posted',
                'created_by' => Auth::id(),
            ]);

            $bankLine = [
                'account_id' => $bankAccountId,
                'description' => $transaction->description,
                'debit' => $transaction->type === 'deposit' ? $amount : 0,
                'credit' => $transaction->type === 'withdrawal' ? $amount : 0,
            ];

            $offsetLine = [
                'account_id' => $offsetAccount->id,
                'description' => $transaction->description,
                'debit' => $transaction->type === 'withdrawal' ? $amount : 0,
                'credit' => $transaction->type === 'deposit' ? $amount : 0,
            ];

            $journal->lines()->createMany([$bankLine, $offsetLine]);

            $transaction->update([
                'matched_journal_id' => $journal->id,
                'category_id' => $offsetAccount->id,
                'status' => 'matched',
            ]);

            return $journal->load('lines.account', 'creator');
        });
    }

    protected function descriptionSimilarity(?string $transactionDescription, ?string $journalDescription, ?string $accountName): bool
    {
        $transactionDescription = $this->normalize($transactionDescription);
        $journalDescription = $this->normalize($journalDescription);
        $accountName = $this->normalize($accountName);

        if ($transactionDescription === '') {
            return false;
        }

        foreach ([$journalDescription, $accountName] as $candidate) {
            if ($candidate === '') {
                continue;
            }

            if (str_contains($transactionDescription, $candidate) || str_contains($candidate, $transactionDescription)) {
                return true;
            }

            similar_text($transactionDescription, $candidate, $percent);

            if ($percent >= 55) {
                return true;
            }

            if (levenshtein($transactionDescription, $candidate) <= 6) {
                return true;
            }
        }

        return false;
    }

    protected function normalize(?string $value): string
    {
        return (string) preg_replace('/\s+/', ' ', strtolower(trim((string) $value)));
    }

    protected function resolveBankAccountId(Transaction $transaction): int
    {
        if ($transaction->bank_account_id) {
            return (int) $transaction->bank_account_id;
        }

        $defaultBankAccountId = AccountDefault::query()
            ->byCompany($transaction->company_id)
            ->where('type', 'bank_checking')
            ->value('account_id');

        if ($defaultBankAccountId) {
            return (int) $defaultBankAccountId;
        }

        throw new RuntimeException('A bank account is required before creating a journal entry from this transaction.');
    }
}

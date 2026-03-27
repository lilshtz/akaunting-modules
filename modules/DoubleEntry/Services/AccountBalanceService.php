<?php

namespace Modules\DoubleEntry\Services;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\DoubleEntry\Models\Account;
use Modules\DoubleEntry\Models\AccountDefault;
use Modules\DoubleEntry\Models\Journal;
use Modules\DoubleEntry\Models\JournalLine;

class AccountBalanceService
{
    public function accountOptions(): array
    {
        return Account::where('company_id', company_id())
            ->orderBy('code')
            ->get()
            ->mapWithKeys(function (Account $account) {
                return [$account->id => $account->code . ' - ' . $account->name];
            })
            ->all();
    }

    public function defaultMappings(): Collection
    {
        $keys = array_keys(trans('double-entry::general.defaults'));

        return AccountDefault::with('account')
            ->where('company_id', company_id())
            ->whereIn('key', $keys)
            ->get()
            ->keyBy('key');
    }

    public function nextJournalNumber(): string
    {
        $count = Journal::where('company_id', company_id())->withTrashed()->count() + 1;

        return 'JE-' . now()->format('Ymd') . '-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    public function syncJournal(Journal $journal, array $attributes, array $lines, bool $post = false): Journal
    {
        return DB::transaction(function () use ($journal, $attributes, $lines, $post) {
            $journal->fill($attributes);
            $journal->company_id = company_id();
            $journal->updated_by = auth()->id();

            if (!$journal->exists) {
                $journal->created_by = auth()->id();
                $journal->number = $journal->number ?: $this->nextJournalNumber();
            }

            $journal->save();

            $journal->lines()->delete();

            $totals = ['debit' => 0.0, 'credit' => 0.0];

            foreach (array_values($lines) as $index => $line) {
                $amount = round((float) ($line['amount'] ?? 0), 4);

                if (empty($line['account_id']) || $amount <= 0 || !in_array($line['entry_type'] ?? null, [JournalLine::DEBIT, JournalLine::CREDIT], true)) {
                    continue;
                }

                $journal->lines()->create([
                    'company_id' => company_id(),
                    'account_id' => $line['account_id'],
                    'line_number' => $index + 1,
                    'entry_type' => $line['entry_type'],
                    'description' => $line['description'] ?? null,
                    'amount' => $amount,
                ]);

                $totals[$line['entry_type']] += $amount;
            }

            $journal->forceFill([
                'total_debit' => $totals['debit'],
                'total_credit' => $totals['credit'],
                'status' => $post ? Journal::STATUS_POSTED : ($attributes['status'] ?? Journal::STATUS_DRAFT),
                'posted_at' => $post ? now() : $journal->posted_at,
            ])->save();

            return $journal->fresh(['lines.account']);
        });
    }

    public function journalIsBalanced(array $lines): bool
    {
        $debit = 0.0;
        $credit = 0.0;

        foreach ($lines as $line) {
            $amount = round((float) ($line['amount'] ?? 0), 4);

            if (($line['entry_type'] ?? null) === JournalLine::DEBIT) {
                $debit += $amount;
            }

            if (($line['entry_type'] ?? null) === JournalLine::CREDIT) {
                $credit += $amount;
            }
        }

        return round($debit, 4) === round($credit, 4) && $debit > 0;
    }

    public function voidJournal(Journal $journal, ?string $reason = null): void
    {
        $description = trim(($journal->description ?: '') . PHP_EOL . ($reason ?: 'Voided on ' . now()->toDateTimeString()));

        $journal->forceFill([
            'status' => Journal::STATUS_VOIDED,
            'voided_at' => now(),
            'description' => $description,
            'updated_by' => auth()->id(),
        ])->save();
    }

    public function buildAccountHierarchy(): Collection
    {
        $accounts = Account::where('company_id', company_id())
            ->orderBy('code')
            ->get();

        return $this->flattenAccounts($accounts);
    }

    public function buildGeneralLedger(?int $accountId, ?string $dateFrom, ?string $dateTo): Collection
    {
        $accounts = Account::where('company_id', company_id())
            ->when($accountId, fn ($query) => $query->where('id', $accountId))
            ->orderBy('code')
            ->get();

        return $accounts->map(function (Account $account) use ($dateFrom, $dateTo) {
            $running = $account->opening_balance;

            $lines = JournalLine::query()
                ->where('company_id', company_id())
                ->where('account_id', $account->id)
                ->whereHas('journal', function ($query) use ($dateFrom, $dateTo) {
                    $query->where('company_id', company_id())
                        ->where('status', Journal::STATUS_POSTED)
                        ->when($dateFrom, fn ($inner) => $inner->whereDate('date', '>=', $dateFrom))
                        ->when($dateTo, fn ($inner) => $inner->whereDate('date', '<=', $dateTo));
                })
                ->with('journal')
                ->get()
                ->sortBy(fn (JournalLine $line) => (optional($line->journal->date)->format('Ymd') ?: '00000000') . '-' . str_pad((string) $line->line_number, 5, '0', STR_PAD_LEFT))
                ->map(function (JournalLine $line) use (&$running, $account) {
                    $signed = $this->signedAmount($account, $line->entry_type, $line->amount);
                    $running += $signed;

                    return [
                        'date' => optional($line->journal->date)->format('Y-m-d'),
                        'journal_number' => $line->journal->number,
                        'reference' => $line->journal->reference,
                        'description' => $line->description ?: $line->journal->description,
                        'debit' => $line->entry_type === JournalLine::DEBIT ? $line->amount : 0,
                        'credit' => $line->entry_type === JournalLine::CREDIT ? $line->amount : 0,
                        'running_balance' => $running,
                    ];
                });

            return [
                'account' => $account,
                'opening_balance' => $account->opening_balance,
                'lines' => $lines,
                'closing_balance' => $running,
            ];
        });
    }

    public function buildTrialBalance(?string $dateTo): Collection
    {
        return Account::where('company_id', company_id())
            ->orderBy('code')
            ->get()
            ->map(function (Account $account) use ($dateTo) {
                $summary = $this->accountSummary($account, null, $dateTo);

                return [
                    'account' => $account,
                    'debit' => $summary['debit_balance'],
                    'credit' => $summary['credit_balance'],
                    'raw_balance' => $summary['balance'],
                ];
            });
    }

    public function buildBalanceSheet(?string $dateTo): array
    {
        $sections = [
            Account::TYPE_ASSET => collect(),
            Account::TYPE_LIABILITY => collect(),
            Account::TYPE_EQUITY => collect(),
        ];

        foreach (Account::where('company_id', company_id())
            ->whereIn('type', array_keys($sections))
            ->orderBy('code')
            ->get() as $account) {
            $summary = $this->accountSummary($account, null, $dateTo);

            $sections[$account->type]->push([
                'account' => $account,
                'balance' => abs($summary['balance']),
            ]);
        }

        return [
            'sections' => $sections,
            'totals' => collect($sections)->map(fn (Collection $items) => $items->sum('balance')),
        ];
    }

    public function buildProfitLoss(?string $dateFrom, ?string $dateTo): array
    {
        $income = collect();
        $expense = collect();

        foreach (Account::where('company_id', company_id())
            ->whereIn('type', [Account::TYPE_INCOME, Account::TYPE_EXPENSE])
            ->orderBy('code')
            ->get() as $account) {
            $summary = $this->accountSummary($account, $dateFrom, $dateTo, false);
            $row = [
                'account' => $account,
                'balance' => abs($summary['balance']),
            ];

            if ($account->type === Account::TYPE_INCOME) {
                $income->push($row);
            } else {
                $expense->push($row);
            }
        }

        return [
            'income' => $income,
            'expense' => $expense,
            'totals' => [
                'income' => $income->sum('balance'),
                'expense' => $expense->sum('balance'),
            ],
        ];
    }

    public function exportIfRequested(Request $request, string $filename, array $payload, string $view): View|\Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\Response|null
    {
        $format = $request->get('format');

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($payload) {
                $handle = fopen('php://output', 'w');

                foreach ($payload as $row) {
                    fputcsv($handle, $row);
                }

                fclose($handle);
            }, $filename . '.csv', ['Content-Type' => 'text/csv']);
        }

        if ($format === 'pdf' && app()->bound('dompdf.wrapper')) {
            $html = '<table border="1" cellspacing="0" cellpadding="6" width="100%">';

            foreach ($payload as $index => $row) {
                $tag = $index === 0 ? 'th' : 'td';
                $html .= '<tr>';

                foreach ($row as $cell) {
                    $html .= '<' . $tag . '>' . e((string) $cell) . '</' . $tag . '>';
                }

                $html .= '</tr>';
            }

            $html .= '</table>';

            return app('dompdf.wrapper')->loadHTML($html)->download($filename . '.pdf');
        }

        return null;
    }

    public function upsertAutoJournal(string $sourceType, int $sourceId, array $attributes, array $lines): ?Journal
    {
        if (!$this->journalIsBalanced($lines)) {
            return null;
        }

        $journal = Journal::firstOrNew([
            'company_id' => company_id(),
            'source_type' => $sourceType,
            'source_id' => $sourceId,
        ]);

        $attributes['number'] = $journal->number ?: $this->nextJournalNumber();
        $attributes['status'] = Journal::STATUS_POSTED;
        $attributes['source_type'] = $sourceType;
        $attributes['source_id'] = $sourceId;

        return $this->syncJournal($journal, $attributes, $lines, true);
    }

    protected function flattenAccounts(Collection $accounts, ?int $parentId = null, int $depth = 0): Collection
    {
        $rows = collect();

        foreach ($accounts->where('parent_id', $parentId) as $account) {
            $account->depth = $depth;
            $rows->push($account);
            $rows = $rows->merge($this->flattenAccounts($accounts, $account->id, $depth + 1));
        }

        return $rows;
    }

    protected function accountSummary(Account $account, ?string $dateFrom, ?string $dateTo, bool $includeOpening = true): array
    {
        $debits = JournalLine::query()
            ->where('company_id', company_id())
            ->where('account_id', $account->id)
            ->where('entry_type', JournalLine::DEBIT)
            ->whereHas('journal', function ($query) use ($dateFrom, $dateTo) {
                $query->where('company_id', company_id())
                    ->where('status', Journal::STATUS_POSTED)
                    ->when($dateFrom, fn ($inner) => $inner->whereDate('date', '>=', $dateFrom))
                    ->when($dateTo, fn ($inner) => $inner->whereDate('date', '<=', $dateTo));
            })
            ->sum('amount');

        $credits = JournalLine::query()
            ->where('company_id', company_id())
            ->where('account_id', $account->id)
            ->where('entry_type', JournalLine::CREDIT)
            ->whereHas('journal', function ($query) use ($dateFrom, $dateTo) {
                $query->where('company_id', company_id())
                    ->where('status', Journal::STATUS_POSTED)
                    ->when($dateFrom, fn ($inner) => $inner->whereDate('date', '>=', $dateFrom))
                    ->when($dateTo, fn ($inner) => $inner->whereDate('date', '<=', $dateTo));
            })
            ->sum('amount');

        $balance = $includeOpening ? (float) $account->opening_balance : 0.0;
        $balance += $this->signedAmount($account, JournalLine::DEBIT, $debits);
        $balance += $this->signedAmount($account, JournalLine::CREDIT, $credits);

        return [
            'debits' => $debits,
            'credits' => $credits,
            'balance' => $balance,
            'debit_balance' => $balance >= 0 ? $balance : 0,
            'credit_balance' => $balance < 0 ? abs($balance) : 0,
        ];
    }

    protected function signedAmount(Account $account, string $entryType, float $amount): float
    {
        $normal = $account->normal_balance;

        return $entryType === $normal ? $amount : -$amount;
    }
}

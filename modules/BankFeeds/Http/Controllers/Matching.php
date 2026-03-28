<?php

namespace Modules\BankFeeds\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\BankFeeds\Models\Transaction;
use Modules\BankFeeds\Services\TransactionMatcher;
use Modules\DoubleEntry\Models\Account;

class Matching extends Controller
{
    public function __construct(protected TransactionMatcher $matcher)
    {
    }

    public function index(Request $request)
    {
        $status = $request->string('status')->toString() ?: 'unmatched';
        $confidenceThreshold = max(0, min(100, (int) $request->integer('confidence_threshold', 0)));

        $query = Transaction::query()
            ->byCompany()
            ->with(['bankAccount', 'category', 'matchedJournal'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($status === 'unmatched') {
            $query->whereIn('status', ['pending', 'categorized']);
        } elseif (in_array($status, ['matched', 'ignored'], true)) {
            $query->where('status', $status);
        }

        $transactions = $query->paginate(25)->withQueryString();

        $suggestions = [];

        foreach ($transactions as $transaction) {
            if (in_array($transaction->status, ['pending', 'categorized'], true)) {
                $matches = $this->matcher->findMatches($transaction);

                if ($confidenceThreshold > 0 && (($matches[0]['score'] ?? 0) < $confidenceThreshold)) {
                    continue;
                }

                $suggestions[$transaction->id] = $matches;
            } else {
                $suggestions[$transaction->id] = [];
            }
        }

        if ($confidenceThreshold > 0) {
            $transactions->setCollection(
                $transactions->getCollection()->filter(function (Transaction $transaction) use ($suggestions): bool {
                    return ! in_array($transaction->status, ['pending', 'categorized'], true)
                        || ! empty($suggestions[$transaction->id]);
                })->values()
            );
        }

        $summary = [
            'unmatched' => Transaction::query()->byCompany()->whereIn('status', ['pending', 'categorized'])->count(),
            'high_confidence' => Transaction::query()
                ->byCompany()
                ->whereIn('status', ['pending', 'categorized'])
                ->get()
                ->filter(fn (Transaction $transaction) => (($this->matcher->findMatches($transaction)[0]['score'] ?? 0) > 80))
                ->count(),
            'ignored' => Transaction::query()->byCompany()->where('status', 'ignored')->count(),
        ];

        return view('bank-feeds::matching.index', compact('transactions', 'suggestions', 'summary', 'status', 'confidenceThreshold'));
    }

    public function show(int $id)
    {
        $transaction = Transaction::query()
            ->byCompany()
            ->with(['bankAccount', 'category', 'matchedJournal'])
            ->findOrFail($id);

        $matches = $this->matcher->findMatches($transaction);
        $accountOptions = Account::query()
            ->byCompany()
            ->where('enabled', true)
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (Account $account) => [$account->id => trim($account->code . ' - ' . $account->name)])
            ->all();

        return view('bank-feeds::matching.show', compact('transaction', 'matches', 'accountOptions'));
    }

    public function accept(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'journal_id' => ['required', 'integer'],
        ]);

        Transaction::query()->byCompany()->findOrFail($id);
        $this->matcher->acceptMatch($id, (int) $validated['journal_id']);

        flash(trans('bank-feeds::general.messages.match_accepted'))->success();

        return redirect()->route('bank-feeds.matching.show', $id);
    }

    public function reject(int $id): RedirectResponse
    {
        Transaction::query()->byCompany()->findOrFail($id);
        $this->matcher->rejectMatch($id);

        flash(trans('bank-feeds::general.messages.match_rejected'))->success();

        return redirect()->route('bank-feeds.matching.show', $id);
    }

    public function createJournal(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'account_id' => ['required', 'integer'],
        ]);

        $transaction = Transaction::query()->byCompany()->findOrFail($id);
        $journal = $this->matcher->createJournalFromTransaction($transaction, (int) $validated['account_id']);

        flash(trans('bank-feeds::general.messages.journal_created_from_transaction'))->success();

        return redirect()->route('double-entry.journals.show', $journal->id);
    }

    public function autoMatch(): RedirectResponse
    {
        $count = $this->matcher->autoMatchAll(company_id());

        flash(trans('bank-feeds::general.messages.auto_matched', ['count' => $count]))->success();

        return redirect()->route('bank-feeds.matching.index');
    }

    public function bulkIgnore(Request $request): RedirectResponse
    {
        $ids = collect($request->input('transaction_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($ids->isEmpty()) {
            flash(trans('bank-feeds::general.messages.no_transactions_selected'))->warning();

            return redirect()->route('bank-feeds.matching.index');
        }

        $count = Transaction::query()
            ->byCompany()
            ->whereIn('id', $ids)
            ->update([
                'matched_journal_id' => null,
                'status' => 'ignored',
            ]);

        flash(trans('bank-feeds::general.messages.bulk_ignored', ['count' => $count]))->success();

        return redirect()->route('bank-feeds.matching.index');
    }
}

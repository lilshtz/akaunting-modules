<?php

namespace Modules\BankFeeds\Http\Controllers;

use App\Abstracts\Http\Controller;
use App\Models\Banking\Account;
use Illuminate\Http\Request;
use Modules\BankFeeds\Models\BankFeedReconciliation;
use Modules\BankFeeds\Models\BankFeedTransaction;
use Modules\BankFeeds\Services\TransactionMatcher;

class Reconciliation extends Controller
{
    protected TransactionMatcher $matcher;

    public function __construct(TransactionMatcher $matcher)
    {
        $this->matcher = $matcher;
    }

    /**
     * List all reconciliations and option to start new one.
     */
    public function index()
    {
        $reconciliations = BankFeedReconciliation::where('company_id', company_id())
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        $accounts = Account::where('company_id', company_id())
            ->where('enabled', true)
            ->pluck('name', 'id');

        return view('bank-feeds::reconciliation.index', compact('reconciliations', 'accounts'));
    }

    /**
     * Create a new reconciliation session.
     */
    public function create(Request $request)
    {
        $request->validate([
            'bank_account_id' => 'required|integer',
            'statement_start_date' => 'required|date',
            'statement_end_date' => 'required|date|after_or_equal:statement_start_date',
            'opening_balance' => 'required|numeric',
            'closing_balance' => 'required|numeric',
        ]);

        $reconciliation = BankFeedReconciliation::create([
            'company_id' => company_id(),
            'bank_account_id' => $request->get('bank_account_id'),
            'statement_start_date' => $request->get('statement_start_date'),
            'statement_end_date' => $request->get('statement_end_date'),
            'opening_balance' => $request->get('opening_balance'),
            'closing_balance' => $request->get('closing_balance'),
            'status' => BankFeedReconciliation::STATUS_IN_PROGRESS,
        ]);

        return redirect()->route('bank-feeds.reconciliation.show', $reconciliation->id);
    }

    /**
     * Show reconciliation workspace.
     */
    public function show(int $id)
    {
        $reconciliation = BankFeedReconciliation::where('company_id', company_id())
            ->findOrFail($id);

        // Get imported transactions for this account and period
        $bankTransactions = BankFeedTransaction::whereHas('import', function ($q) {
            $q->where('company_id', company_id());
        })
            ->where('bank_account_id', $reconciliation->bank_account_id)
            ->whereBetween('date', [
                $reconciliation->statement_start_date,
                $reconciliation->statement_end_date,
            ])
            ->with(['category', 'matchedTransaction'])
            ->orderBy('date')
            ->get();

        // Calculate reconciliation totals
        $totals = $this->calculateTotals($reconciliation, $bankTransactions);

        $account = Account::find($reconciliation->bank_account_id);

        return view('bank-feeds::reconciliation.show', compact(
            'reconciliation', 'bankTransactions', 'totals', 'account'
        ));
    }

    /**
     * Match a transaction within a reconciliation.
     */
    public function matchTransaction(Request $request, int $id)
    {
        $reconciliation = BankFeedReconciliation::where('company_id', company_id())
            ->findOrFail($id);

        $request->validate([
            'bank_feed_transaction_id' => 'required|integer',
            'transaction_id' => 'required|integer',
        ]);

        $bankTxn = BankFeedTransaction::findOrFail($request->get('bank_feed_transaction_id'));

        $this->matcher->applyMatch($bankTxn, $request->get('transaction_id'), 100.0);

        $bankTxn->update(['reconciliation_id' => $reconciliation->id]);

        $this->recalculate($reconciliation);

        flash(trans('bank-feeds::general.messages.match_accepted'))->success();

        return redirect()->route('bank-feeds.reconciliation.show', $id);
    }

    /**
     * Unmatch a transaction within a reconciliation.
     */
    public function unmatchTransaction(Request $request, int $id)
    {
        $reconciliation = BankFeedReconciliation::where('company_id', company_id())
            ->findOrFail($id);

        $request->validate([
            'bank_feed_transaction_id' => 'required|integer',
        ]);

        $bankTxn = BankFeedTransaction::findOrFail($request->get('bank_feed_transaction_id'));

        $this->matcher->unmatch($bankTxn);
        $bankTxn->update(['reconciliation_id' => null]);

        $this->recalculate($reconciliation);

        flash(trans('bank-feeds::general.messages.match_rejected'))->success();

        return redirect()->route('bank-feeds.reconciliation.show', $id);
    }

    /**
     * Mark a reconciliation period as completed.
     */
    public function complete(int $id)
    {
        $reconciliation = BankFeedReconciliation::where('company_id', company_id())
            ->findOrFail($id);

        // Recalculate to ensure current state
        $bankTransactions = BankFeedTransaction::whereHas('import', function ($q) {
            $q->where('company_id', company_id());
        })
            ->where('bank_account_id', $reconciliation->bank_account_id)
            ->whereBetween('date', [
                $reconciliation->statement_start_date,
                $reconciliation->statement_end_date,
            ])
            ->get();

        $totals = $this->calculateTotals($reconciliation, $bankTransactions);

        // Store matched transaction IDs
        $matchedIds = $bankTransactions->where('status', BankFeedTransaction::STATUS_MATCHED)
            ->pluck('matched_transaction_id')
            ->filter()
            ->values()
            ->toArray();

        $reconciliation->update([
            'status' => BankFeedReconciliation::STATUS_COMPLETED,
            'reconciled_balance' => $totals['reconciled_balance'],
            'difference' => $totals['difference'],
            'matched_transaction_ids' => $matchedIds,
            'completed_at' => now(),
        ]);

        // Mark the actual Akaunting transactions as reconciled
        if (!empty($matchedIds)) {
            \App\Models\Banking\Transaction::whereIn('id', $matchedIds)
                ->update(['reconciled' => true]);
        }

        flash(trans('bank-feeds::general.messages.reconciliation_completed'))->success();

        return redirect()->route('bank-feeds.reconciliation.index');
    }

    /**
     * Delete a reconciliation.
     */
    public function destroy(int $id)
    {
        $reconciliation = BankFeedReconciliation::where('company_id', company_id())
            ->findOrFail($id);

        // Un-link bank feed transactions from this reconciliation
        BankFeedTransaction::where('reconciliation_id', $reconciliation->id)
            ->update(['reconciliation_id' => null]);

        $reconciliation->delete();

        flash(trans('messages.success.deleted', ['type' => trans('bank-feeds::general.reconciliation')]))->success();

        return redirect()->route('bank-feeds.reconciliation.index');
    }

    /**
     * Calculate reconciliation totals.
     */
    protected function calculateTotals(BankFeedReconciliation $reconciliation, $bankTransactions): array
    {
        $matchedDeposits = 0;
        $matchedWithdrawals = 0;
        $unmatchedCount = 0;
        $matchedCount = 0;
        $ignoredCount = 0;

        foreach ($bankTransactions as $txn) {
            if ($txn->status === BankFeedTransaction::STATUS_MATCHED) {
                $matchedCount++;
                if ($txn->type === BankFeedTransaction::TYPE_DEPOSIT) {
                    $matchedDeposits += abs($txn->amount);
                } else {
                    $matchedWithdrawals += abs($txn->amount);
                }
            } elseif ($txn->status === BankFeedTransaction::STATUS_IGNORED) {
                $ignoredCount++;
            } else {
                $unmatchedCount++;
            }
        }

        $reconciledBalance = $reconciliation->opening_balance + $matchedDeposits - $matchedWithdrawals;
        $difference = $reconciliation->closing_balance - $reconciledBalance;

        return [
            'matched_deposits' => $matchedDeposits,
            'matched_withdrawals' => $matchedWithdrawals,
            'reconciled_balance' => round($reconciledBalance, 4),
            'difference' => round($difference, 4),
            'matched_count' => $matchedCount,
            'unmatched_count' => $unmatchedCount,
            'ignored_count' => $ignoredCount,
            'total_count' => $bankTransactions->count(),
        ];
    }

    /**
     * Recalculate and update a reconciliation's balance fields.
     */
    protected function recalculate(BankFeedReconciliation $reconciliation): void
    {
        $bankTransactions = BankFeedTransaction::whereHas('import', function ($q) {
            $q->where('company_id', company_id());
        })
            ->where('bank_account_id', $reconciliation->bank_account_id)
            ->whereBetween('date', [
                $reconciliation->statement_start_date,
                $reconciliation->statement_end_date,
            ])
            ->get();

        $totals = $this->calculateTotals($reconciliation, $bankTransactions);

        $reconciliation->update([
            'reconciled_balance' => $totals['reconciled_balance'],
            'difference' => $totals['difference'],
        ]);
    }
}

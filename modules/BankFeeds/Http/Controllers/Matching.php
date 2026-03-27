<?php

namespace Modules\BankFeeds\Http\Controllers;

use App\Abstracts\Http\Controller;
use App\Models\Banking\Transaction;
use Illuminate\Http\Request;
use Modules\BankFeeds\Models\BankFeedTransaction;
use Modules\BankFeeds\Services\TransactionMatcher;

class Matching extends Controller
{
    protected TransactionMatcher $matcher;

    public function __construct(TransactionMatcher $matcher)
    {
        $this->matcher = $matcher;
    }

    /**
     * Show all unmatched transactions with suggested matches.
     */
    public function index(Request $request)
    {
        $query = BankFeedTransaction::whereHas('import', function ($q) {
            $q->where('company_id', company_id());
        })->unmatched();

        if ($request->has('bank_account_id')) {
            $query->where('bank_account_id', $request->get('bank_account_id'));
        }

        $transactions = $query->with(['category', 'import'])
            ->orderBy('date', 'desc')
            ->paginate(25);

        // Pre-compute suggestions for each transaction
        $suggestions = [];
        foreach ($transactions as $txn) {
            $suggestions[$txn->id] = $this->matcher->findMatches($txn);
        }

        $accounts = $this->getBankAccounts();

        return view('bank-feeds::matching.index', compact('transactions', 'suggestions', 'accounts'));
    }

    /**
     * Show match detail for a single bank feed transaction.
     */
    public function show(int $id)
    {
        $transaction = BankFeedTransaction::whereHas('import', function ($q) {
            $q->where('company_id', company_id());
        })->findOrFail($id);

        $suggestions = $this->matcher->findMatches($transaction);

        return view('bank-feeds::matching.show', compact('transaction', 'suggestions'));
    }

    /**
     * Accept a match between a bank feed transaction and an Akaunting transaction.
     */
    public function acceptMatch(Request $request, int $id)
    {
        $request->validate([
            'transaction_id' => 'required|integer',
        ]);

        $bankTxn = BankFeedTransaction::whereHas('import', function ($q) {
            $q->where('company_id', company_id());
        })->findOrFail($id);

        // Verify the Akaunting transaction exists and belongs to the same account
        Transaction::where('company_id', company_id())
            ->where('account_id', $bankTxn->bank_account_id)
            ->findOrFail($request->get('transaction_id'));

        $this->matcher->applyMatch($bankTxn, $request->get('transaction_id'), $request->get('confidence'));

        flash(trans('bank-feeds::general.messages.match_accepted'))->success();

        return redirect()->route('bank-feeds.matching.index');
    }

    /**
     * Reject a match — keep as unmatched.
     */
    public function rejectMatch(int $id)
    {
        $bankTxn = BankFeedTransaction::whereHas('import', function ($q) {
            $q->where('company_id', company_id());
        })->findOrFail($id);

        if ($bankTxn->status === BankFeedTransaction::STATUS_MATCHED) {
            $this->matcher->unmatch($bankTxn);
        }

        flash(trans('bank-feeds::general.messages.match_rejected'))->success();

        return redirect()->route('bank-feeds.matching.index');
    }

    /**
     * Create a new bill or payment from an unmatched transaction.
     */
    public function createTransaction(Request $request, int $id)
    {
        $bankTxn = BankFeedTransaction::whereHas('import', function ($q) {
            $q->where('company_id', company_id());
        })->findOrFail($id);

        $type = $bankTxn->type === BankFeedTransaction::TYPE_WITHDRAWAL ? 'expense' : 'income';

        $newTransaction = Transaction::create([
            'company_id' => company_id(),
            'type' => $type,
            'account_id' => $bankTxn->bank_account_id,
            'paid_at' => $bankTxn->date->format('Y-m-d H:i:s'),
            'amount' => abs($bankTxn->amount),
            'currency_code' => setting('default.currency', 'USD'),
            'currency_rate' => 1,
            'description' => $bankTxn->description,
            'category_id' => $bankTxn->category_id ?? setting('default.income_category', 1),
            'payment_method' => 'bank_transfer',
            'reference' => 'Bank Feed Import #' . $bankTxn->import_id,
        ]);

        $this->matcher->applyMatch($bankTxn, $newTransaction->id, 100.0);

        flash(trans('bank-feeds::general.messages.transaction_created'))->success();

        return redirect()->route('bank-feeds.matching.index');
    }

    /**
     * Auto-match all high-confidence transactions.
     */
    public function autoMatchAll()
    {
        $matched = $this->matcher->autoMatch(company_id());

        flash(trans('bank-feeds::general.messages.auto_matched', ['count' => $matched]))->success();

        return redirect()->route('bank-feeds.matching.index');
    }

    /**
     * Bulk ignore selected transactions.
     */
    public function bulkIgnore(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $updated = BankFeedTransaction::whereHas('import', function ($q) {
            $q->where('company_id', company_id());
        })
            ->whereIn('id', $request->get('ids'))
            ->whereIn('status', [BankFeedTransaction::STATUS_PENDING, BankFeedTransaction::STATUS_CATEGORIZED])
            ->update(['status' => BankFeedTransaction::STATUS_IGNORED]);

        flash(trans('bank-feeds::general.messages.bulk_ignored', ['count' => $updated]))->success();

        return redirect()->route('bank-feeds.matching.index');
    }

    protected function getBankAccounts(): array
    {
        $accounts = \App\Models\Banking\Account::where('company_id', company_id())
            ->where('enabled', true)
            ->pluck('name', 'id')
            ->toArray();

        return ['' => trans('general.all')] + $accounts;
    }
}

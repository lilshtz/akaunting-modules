<?php

namespace Modules\BankFeeds\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\BankFeeds\Models\Reconciliation as ReconciliationModel;
use Modules\DoubleEntry\Models\Account;

class Reconciliation extends Controller
{
    public function index()
    {
        $reconciliations = ReconciliationModel::query()
            ->byCompany()
            ->with('bankAccount')
            ->orderByDesc('period_end')
            ->orderByDesc('id')
            ->paginate(25);

        return view('bank-feeds::reconciliation.index', compact('reconciliations'));
    }

    public function create()
    {
        return view('bank-feeds::reconciliation.create', [
            'bankAccountOptions' => $this->bankAccountOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bank_account_id' => ['required', 'integer'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'opening_balance' => ['required', 'numeric'],
            'closing_balance' => ['required', 'numeric'],
        ]);

        $bankAccount = Account::query()
            ->byCompany()
            ->where('type', 'asset')
            ->findOrFail((int) $validated['bank_account_id']);

        $reconciliation = ReconciliationModel::create([
            'company_id' => company_id(),
            'bank_account_id' => $bankAccount->id,
            'period_start' => $validated['period_start'],
            'period_end' => $validated['period_end'],
            'opening_balance' => $validated['opening_balance'],
            'closing_balance' => $validated['closing_balance'],
            'status' => 'draft',
        ]);

        flash(trans('messages.success.added', ['type' => trans('bank-feeds::general.reconciliation')]))->success();

        return redirect()->route('bank-feeds.reconciliation.show', $reconciliation->id);
    }

    public function show(int $id)
    {
        $reconciliation = ReconciliationModel::query()
            ->byCompany()
            ->with('bankAccount')
            ->findOrFail($id);

        $transactions = $reconciliation->transactions()
            ->with('matchedJournal')
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $deposits = (float) $transactions->where('type', 'deposit')->sum('amount');
        $withdrawals = abs((float) $transactions->where('type', 'withdrawal')->sum('amount'));
        $computedBalance = round((float) $reconciliation->opening_balance + $deposits - $withdrawals, 4);
        $difference = round($computedBalance - (float) $reconciliation->closing_balance, 4);
        $canComplete = abs($difference) < 0.0001;

        return view('bank-feeds::reconciliation.show', compact(
            'reconciliation',
            'transactions',
            'deposits',
            'withdrawals',
            'computedBalance',
            'difference',
            'canComplete'
        ));
    }

    public function complete(int $id): RedirectResponse
    {
        $reconciliation = ReconciliationModel::query()
            ->byCompany()
            ->findOrFail($id);

        $transactions = $reconciliation->transactions()->get();
        $deposits = (float) $transactions->where('type', 'deposit')->sum('amount');
        $withdrawals = abs((float) $transactions->where('type', 'withdrawal')->sum('amount'));
        $difference = round(((float) $reconciliation->opening_balance + $deposits - $withdrawals) - (float) $reconciliation->closing_balance, 4);

        if (abs($difference) >= 0.0001) {
            flash(trans('bank-feeds::general.messages.reconciliation_not_balanced'))->error();

            return redirect()->route('bank-feeds.reconciliation.show', $reconciliation->id);
        }

        $reconciliation->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        flash(trans('bank-feeds::general.messages.reconciliation_completed'))->success();

        return redirect()->route('bank-feeds.reconciliation.show', $reconciliation->id);
    }

    protected function bankAccountOptions(): array
    {
        return Account::query()
            ->byCompany()
            ->where('type', 'asset')
            ->where('enabled', true)
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (Account $account) => [$account->id => trim($account->code . ' - ' . $account->name)])
            ->all();
    }
}

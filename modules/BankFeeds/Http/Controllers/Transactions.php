<?php

namespace Modules\BankFeeds\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\BankFeeds\Models\Import;
use Modules\BankFeeds\Models\Transaction;

class Transactions extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::query()
            ->byCompany()
            ->with(['category', 'import'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('import')) {
            $query->where('import_id', $request->integer('import'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->get('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->get('date_to'));
        }

        $transactions = $query->paginate(25)->withQueryString();
        $imports = Import::query()->byCompany()->orderByDesc('id')->get();

        return view('bank-feeds::transactions.index', compact('transactions', 'imports'));
    }

    public function ignore(int $id): RedirectResponse
    {
        $transaction = Transaction::query()->byCompany()->findOrFail($id);
        $transaction->update(['status' => 'ignored']);

        flash(trans('bank-feeds::general.messages.transaction_ignored'))->success();

        return redirect()->route('bank-feeds.transactions.index');
    }

    public function bulkIgnore(Request $request): RedirectResponse
    {
        $ids = collect($request->input('transaction_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($ids->isEmpty()) {
            flash(trans('bank-feeds::general.messages.no_transactions_selected'))->warning();

            return redirect()->route('bank-feeds.transactions.index');
        }

        $count = Transaction::query()
            ->byCompany()
            ->whereIn('id', $ids)
            ->update(['status' => 'ignored']);

        flash(trans('bank-feeds::general.messages.bulk_ignored', ['count' => $count]))->success();

        return redirect()->route('bank-feeds.transactions.index');
    }
}

<?php

namespace Modules\BankFeeds\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Modules\BankFeeds\Models\BankFeedTransaction;
use Modules\BankFeeds\Models\BankFeedImport;
use Modules\BankFeeds\Services\CategorizationService;

class Transactions extends Controller
{
    protected CategorizationService $categorizationService;

    public function __construct(CategorizationService $categorizationService)
    {
        $this->categorizationService = $categorizationService;
    }

    public function index(Request $request)
    {
        $query = BankFeedTransaction::whereHas('import', function ($q) {
            $q->where('company_id', company_id());
        });

        if ($request->has('import_id')) {
            $query->where('import_id', $request->get('import_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('bank_account_id')) {
            $query->where('bank_account_id', $request->get('bank_account_id'));
        }

        $transactions = $query->with(['category', 'import'])
            ->orderBy('date', 'desc')
            ->paginate(50);

        $statuses = [
            '' => trans('general.all'),
            BankFeedTransaction::STATUS_PENDING => trans('bank-feeds::general.statuses.pending'),
            BankFeedTransaction::STATUS_CATEGORIZED => trans('bank-feeds::general.statuses.categorized'),
            BankFeedTransaction::STATUS_MATCHED => trans('bank-feeds::general.statuses.matched'),
            BankFeedTransaction::STATUS_IGNORED => trans('bank-feeds::general.statuses.ignored'),
        ];

        return view('bank-feeds::transactions.index', compact('transactions', 'statuses'));
    }

    public function ignore(int $id)
    {
        $transaction = BankFeedTransaction::whereHas('import', function ($q) {
            $q->where('company_id', company_id());
        })->findOrFail($id);

        $transaction->update(['status' => BankFeedTransaction::STATUS_IGNORED]);

        flash(trans('bank-feeds::general.messages.transaction_ignored'))->success();

        return redirect()->back();
    }

    /**
     * Bulk re-categorize all pending transactions.
     */
    public function bulkCategorize()
    {
        $categorized = $this->categorizationService->bulkCategorize(company_id());

        flash(trans('bank-feeds::general.messages.bulk_categorized', ['count' => $categorized]))->success();

        return redirect()->route('bank-feeds.transactions.index');
    }
}

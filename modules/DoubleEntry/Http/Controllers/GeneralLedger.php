<?php

namespace Modules\DoubleEntry\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Modules\DoubleEntry\Models\Account;
use Modules\DoubleEntry\Services\AccountBalanceService;

class GeneralLedger extends Controller
{
    /**
     * Display the general ledger report.
     */
    public function index(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfYear()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());
        $accountId = $request->get('account_id');

        $query = Account::where('company_id', company_id())
            ->enabled()
            ->orderBy('code');

        if ($accountId) {
            $query->where('id', $accountId);
        }

        $accounts = $query->get();

        $service = new AccountBalanceService();
        $ledger = $service->getGeneralLedger($accounts, $startDate, $endDate);

        $allAccounts = Account::where('company_id', company_id())
            ->enabled()
            ->orderBy('code')
            ->get();

        return view('double-entry::general-ledger.index', compact(
            'ledger', 'allAccounts', 'startDate', 'endDate', 'accountId'
        ));
    }
}

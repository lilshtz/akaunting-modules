<?php

namespace Modules\DoubleEntry\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Modules\DoubleEntry\Models\Account;
use Modules\DoubleEntry\Services\AccountBalanceService;

class TrialBalance extends Controller
{
    /**
     * Display the trial balance report.
     */
    public function index(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfYear()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());

        $accounts = Account::where('company_id', company_id())
            ->enabled()
            ->orderBy('code')
            ->get();

        $service = new AccountBalanceService();
        $trialBalance = $service->getTrialBalance($accounts, $startDate, $endDate);

        return view('double-entry::trial-balance.index', compact(
            'trialBalance', 'startDate', 'endDate'
        ));
    }
}

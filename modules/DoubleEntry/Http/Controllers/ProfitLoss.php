<?php

namespace Modules\DoubleEntry\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Modules\DoubleEntry\Models\Account;
use Modules\DoubleEntry\Services\AccountBalanceService;

class ProfitLoss extends Controller
{
    /**
     * Display the profit & loss report.
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
        $profitLoss = $service->getProfitLoss($accounts, $startDate, $endDate);

        return view('double-entry::profit-loss.index', compact('profitLoss', 'startDate', 'endDate'));
    }
}

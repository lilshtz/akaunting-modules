<?php

namespace Modules\DoubleEntry\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Modules\DoubleEntry\Models\Account;
use Modules\DoubleEntry\Services\AccountBalanceService;

class BalanceSheet extends Controller
{
    /**
     * Display the balance sheet report.
     */
    public function index(Request $request)
    {
        $endDate = $request->get('end_date', now()->toDateString());

        $accounts = Account::where('company_id', company_id())
            ->enabled()
            ->orderBy('code')
            ->get();

        $service = new AccountBalanceService();
        $balanceSheet = $service->getBalanceSheet($accounts, $endDate);

        return view('double-entry::balance-sheet.index', compact('balanceSheet', 'endDate'));
    }
}

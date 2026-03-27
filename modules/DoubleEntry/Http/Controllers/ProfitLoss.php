<?php

namespace Modules\DoubleEntry\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Modules\DoubleEntry\Services\AccountBalanceService;

class ProfitLoss extends Controller
{
    public function __construct(protected AccountBalanceService $service)
    {
    }

    public function index(Request $request)
    {
        $report = $this->service->buildProfitLoss($request->get('date_from'), $request->get('date_to'));

        $export = $this->service->exportIfRequested($request, 'profit-loss', array_merge(
            [['Section', 'Account', 'Balance']],
            $report['income']->map(fn ($row) => ['Income', $row['account']->code . ' ' . $row['account']->name, $row['balance']])->all(),
            $report['expense']->map(fn ($row) => ['Expense', $row['account']->code . ' ' . $row['account']->name, $row['balance']])->all()
        ), 'double-entry::profit-loss.index');

        if ($export) {
            return $export;
        }

        return view('double-entry::profit-loss.index', [
            'report' => $report,
            'dateFrom' => $request->get('date_from'),
            'dateTo' => $request->get('date_to'),
        ]);
    }
}

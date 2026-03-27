<?php

namespace Modules\DoubleEntry\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Modules\DoubleEntry\Services\AccountBalanceService;

class BalanceSheet extends Controller
{
    public function __construct(protected AccountBalanceService $service)
    {
    }

    public function index(Request $request)
    {
        $report = $this->service->buildBalanceSheet($request->get('date_to'));

        $exportRows = [['Section', 'Account', 'Balance']];

        foreach ($report['sections'] as $section => $rows) {
            foreach ($rows as $row) {
                $exportRows[] = [ucfirst($section), $row['account']->code . ' ' . $row['account']->name, $row['balance']];
            }
        }

        $export = $this->service->exportIfRequested($request, 'balance-sheet', $exportRows, 'double-entry::balance-sheet.index');

        if ($export) {
            return $export;
        }

        return view('double-entry::balance-sheet.index', [
            'report' => $report,
            'dateTo' => $request->get('date_to'),
        ]);
    }
}

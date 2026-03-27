<?php

namespace Modules\DoubleEntry\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Modules\DoubleEntry\Services\AccountBalanceService;

class TrialBalance extends Controller
{
    public function __construct(protected AccountBalanceService $service)
    {
    }

    public function index(Request $request)
    {
        $rows = $this->service->buildTrialBalance($request->get('date_to'));

        $export = $this->service->exportIfRequested($request, 'trial-balance', array_merge(
            [['Account', 'Debit', 'Credit']],
            $rows->map(fn ($row) => [$row['account']->code . ' ' . $row['account']->name, $row['debit'], $row['credit']])->all()
        ), 'double-entry::trial-balance.index');

        if ($export) {
            return $export;
        }

        return view('double-entry::trial-balance.index', [
            'rows' => $rows,
            'dateTo' => $request->get('date_to'),
        ]);
    }
}

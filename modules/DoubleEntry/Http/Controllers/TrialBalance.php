<?php

namespace Modules\DoubleEntry\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Modules\DoubleEntry\Http\Controllers\Concerns\ExportsCsv;
use Modules\DoubleEntry\Services\AccountBalanceService;

class TrialBalance extends Controller
{
    use ExportsCsv;

    public function __construct(protected AccountBalanceService $balances)
    {
    }

    public function index(Request $request)
    {
        $asOfDate = $request->query('as_of_date', now()->toDateString());
        $trialBalance = $this->balances->getTrialBalance(null, $asOfDate);
        $outOfBalance = round($trialBalance['grand_debit'] - $trialBalance['grand_credit'], 4);

        if ($request->query('export') === 'csv') {
            $rows = [];

            foreach ($trialBalance['accounts'] as $type => $accounts) {
                foreach ($accounts as $row) {
                    $rows[] = [
                        ucfirst($type),
                        $row['account']->code,
                        $row['account']->name,
                        number_format($row['debit'], 4, '.', ''),
                        number_format($row['credit'], 4, '.', ''),
                    ];
                }
            }

            $rows[] = [
                'Total',
                '',
                '',
                number_format($trialBalance['grand_debit'], 4, '.', ''),
                number_format($trialBalance['grand_credit'], 4, '.', ''),
            ];

            return $this->streamCsv(
                'trial-balance',
                ['Type', 'Account Code', 'Account Name', 'Debit Balance', 'Credit Balance'],
                $rows,
                $asOfDate
            );
        }

        return view('double-entry::trial-balance.index', compact('trialBalance', 'asOfDate', 'outOfBalance'));
    }
}

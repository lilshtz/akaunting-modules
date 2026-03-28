<?php

namespace Modules\DoubleEntry\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Modules\DoubleEntry\Http\Controllers\Concerns\ExportsCsv;
use Modules\DoubleEntry\Services\AccountBalanceService;

class ProfitLoss extends Controller
{
    use ExportsCsv;

    public function __construct(protected AccountBalanceService $balances)
    {
    }

    public function index(Request $request)
    {
        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now()->endOfMonth()->toDateString());
        $profitLoss = $this->balances->getProfitAndLoss($startDate, $endDate);

        if ($request->query('export') === 'csv') {
            return $this->streamCsv(
                'profit-loss',
                ['Section', 'Account', 'Amount'],
                $this->profitLossCsvRows($profitLoss),
                $endDate
            );
        }

        return view('double-entry::profit-loss.index', compact('profitLoss', 'startDate', 'endDate'));
    }

    protected function profitLossCsvRows(array $profitLoss): array
    {
        $rows = [];

        foreach (['income' => 'Income', 'expenses' => 'Expenses'] as $key => $label) {
            foreach ($profitLoss[$key]['accounts'] as $row) {
                $rows[] = [
                    $label,
                    $row['label'],
                    number_format($row['balance'], 4, '.', ''),
                ];
            }

            $rows[] = [
                $label . ' Total',
                '',
                number_format($profitLoss[$key]['total'], 4, '.', ''),
            ];
        }

        $rows[] = [
            'Net Profit/Loss',
            '',
            number_format($profitLoss['net_profit'], 4, '.', ''),
        ];

        return $rows;
    }
}

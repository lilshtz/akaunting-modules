<?php

namespace Modules\DoubleEntry\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Modules\DoubleEntry\Http\Controllers\Concerns\ExportsCsv;
use Modules\DoubleEntry\Services\AccountBalanceService;

class BalanceSheet extends Controller
{
    use ExportsCsv;

    public function __construct(protected AccountBalanceService $balances)
    {
    }

    public function index(Request $request)
    {
        $asOfDate = $request->query('as_of_date', now()->toDateString());
        $balanceSheet = $this->balances->getBalanceSheet($asOfDate);
        $outOfBalance = round($balanceSheet['total_assets'] - $balanceSheet['total_liabilities_equity'], 4);

        if ($request->query('export') === 'csv') {
            $rows = $this->balanceSheetCsvRows($balanceSheet);

            return $this->streamCsv(
                'balance-sheet',
                ['Section', 'Account', 'Balance'],
                $rows,
                $asOfDate
            );
        }

        return view('double-entry::balance-sheet.index', compact('balanceSheet', 'asOfDate', 'outOfBalance'));
    }

    protected function balanceSheetCsvRows(array $balanceSheet): array
    {
        $rows = [];

        foreach (['assets' => 'Assets', 'liabilities' => 'Liabilities', 'equity' => 'Equity'] as $key => $label) {
            foreach ($balanceSheet[$key]['accounts'] as $row) {
                $rows[] = [
                    $label,
                    $row['label'],
                    number_format($row['balance'], 4, '.', ''),
                ];
            }

            $rows[] = [
                $label . ' Total',
                '',
                number_format($balanceSheet[$key]['total'], 4, '.', ''),
            ];
        }

        $rows[] = [
            'Total Assets',
            '',
            number_format($balanceSheet['total_assets'], 4, '.', ''),
        ];
        $rows[] = [
            'Total Liabilities and Equity',
            '',
            number_format($balanceSheet['total_liabilities_equity'], 4, '.', ''),
        ];

        return $rows;
    }
}

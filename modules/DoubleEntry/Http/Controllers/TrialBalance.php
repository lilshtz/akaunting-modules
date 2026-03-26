<?php

namespace Modules\DoubleEntry\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\DoubleEntry\Services\AccountBalanceService;

class TrialBalance extends Controller
{
    protected AccountBalanceService $balanceService;

    public function __construct(AccountBalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    public function index(Request $request): Response|mixed
    {
        $companyId = company_id();
        $dateFrom = $request->get('date_from', now()->startOfYear()->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());
        $basis = $request->get('basis', 'accrual');

        $trialBalance = $this->balanceService->getTrialBalance($companyId, $dateFrom, $dateTo, $basis);

        $types = ['asset', 'liability', 'equity', 'income', 'expense'];

        return $this->response('double-entry::trial-balance.index', compact(
            'trialBalance', 'types', 'dateFrom', 'dateTo', 'basis'
        ));
    }

    public function export(Request $request): mixed
    {
        $companyId = company_id();
        $dateFrom = $request->get('date_from', now()->startOfYear()->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());
        $basis = $request->get('basis', 'accrual');
        $format = $request->get('format', 'csv');

        $trialBalance = $this->balanceService->getTrialBalance($companyId, $dateFrom, $dateTo, $basis);

        if ($format === 'pdf') {
            $types = ['asset', 'liability', 'equity', 'income', 'expense'];
            $pdf = app('dompdf.wrapper');
            $pdf->loadView('double-entry::trial-balance.pdf', compact('trialBalance', 'types', 'dateFrom', 'dateTo', 'basis'));

            return $pdf->download('trial-balance.pdf');
        }

        // CSV export
        $filename = 'trial-balance-' . $dateTo . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($trialBalance) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Code', 'Account Name', 'Type', 'Debit', 'Credit']);

            foreach ($trialBalance['accounts'] as $type => $accounts) {
                foreach ($accounts as $row) {
                    fputcsv($handle, [
                        $row['account']->code,
                        $row['account']->name,
                        ucfirst($type),
                        $row['debit'] > 0 ? number_format($row['debit'], 2) : '',
                        $row['credit'] > 0 ? number_format($row['credit'], 2) : '',
                    ]);
                }
            }

            fputcsv($handle, ['', '', 'Grand Total', number_format($trialBalance['grand_debit'], 2), number_format($trialBalance['grand_credit'], 2)]);

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}

<?php

namespace Modules\DoubleEntry\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\DoubleEntry\Models\Account;
use Modules\DoubleEntry\Services\AccountBalanceService;

class GeneralLedger extends Controller
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
        $accountId = $request->get('account_id');

        $accounts = Account::where('company_id', $companyId)
            ->enabled()
            ->orderBy('code')
            ->get();

        $accountOptions = $accounts->mapWithKeys(fn ($a) => [$a->id => $a->display_name]);

        $ledgerData = [];

        if ($accountId) {
            // Single account view
            $data = $this->balanceService->getRunningBalance((int) $accountId, $dateFrom, $dateTo, $basis);
            if ($data) {
                $ledgerData[] = $data;
            }
        } else {
            // All accounts
            foreach ($accounts as $account) {
                $data = $this->balanceService->getRunningBalance($account->id, $dateFrom, $dateTo, $basis);
                if ($data && count($data['entries']) > 0) {
                    $ledgerData[] = $data;
                }
            }
        }

        return $this->response('double-entry::general-ledger.index', compact(
            'ledgerData', 'accountOptions', 'dateFrom', 'dateTo', 'basis', 'accountId'
        ));
    }

    public function export(Request $request): mixed
    {
        $companyId = company_id();
        $dateFrom = $request->get('date_from', now()->startOfYear()->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());
        $basis = $request->get('basis', 'accrual');
        $accountId = $request->get('account_id');
        $format = $request->get('format', 'csv');

        $accounts = Account::where('company_id', $companyId)
            ->enabled()
            ->orderBy('code')
            ->get();

        $ledgerData = [];

        if ($accountId) {
            $data = $this->balanceService->getRunningBalance((int) $accountId, $dateFrom, $dateTo, $basis);
            if ($data) {
                $ledgerData[] = $data;
            }
        } else {
            foreach ($accounts as $account) {
                $data = $this->balanceService->getRunningBalance($account->id, $dateFrom, $dateTo, $basis);
                if ($data && count($data['entries']) > 0) {
                    $ledgerData[] = $data;
                }
            }
        }

        if ($format === 'pdf') {
            $pdf = app('dompdf.wrapper');
            $pdf->loadView('double-entry::general-ledger.pdf', compact('ledgerData', 'dateFrom', 'dateTo', 'basis'));

            return $pdf->download('general-ledger.pdf');
        }

        // CSV export
        $filename = 'general-ledger-' . $dateFrom . '-to-' . $dateTo . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($ledgerData) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Account', 'Date', 'Reference', 'Description', 'Debit', 'Credit', 'Balance']);

            foreach ($ledgerData as $accountData) {
                $accountName = $accountData['account']->display_name;
                fputcsv($handle, [$accountName, '', '', 'Opening Balance', '', '', number_format($accountData['opening_balance'], 2)]);

                foreach ($accountData['entries'] as $entry) {
                    fputcsv($handle, [
                        $accountName,
                        $entry['date'],
                        $entry['reference'] ?? '',
                        $entry['description'] ?? '',
                        $entry['debit'] > 0 ? number_format($entry['debit'], 2) : '',
                        $entry['credit'] > 0 ? number_format($entry['credit'], 2) : '',
                        number_format($entry['balance'], 2),
                    ]);
                }

                fputcsv($handle, [$accountName, '', '', 'Closing Balance', '', '', number_format($accountData['closing_balance'], 2)]);
                fputcsv($handle, []);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}

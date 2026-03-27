<?php

namespace Modules\DoubleEntry\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Modules\DoubleEntry\Services\AccountBalanceService;

class GeneralLedger extends Controller
{
    public function __construct(protected AccountBalanceService $service)
    {
    }

    public function index(Request $request)
    {
        $ledger = $this->service->buildGeneralLedger(
            $request->integer('account_id') ?: null,
            $request->get('date_from'),
            $request->get('date_to')
        );

        $export = $this->service->exportIfRequested($request, 'general-ledger', $this->csvRows($ledger), 'double-entry::general-ledger.index');

        if ($export) {
            return $export;
        }

        return view('double-entry::general-ledger.index', [
            'ledger' => $ledger,
            'accounts' => $this->service->accountOptions(),
            'filters' => $request->only(['account_id', 'date_from', 'date_to', 'format']),
        ]);
    }

    protected function csvRows($ledger): array
    {
        $rows = [['Account', 'Date', 'Journal', 'Reference', 'Description', 'Debit', 'Credit', 'Running Balance']];

        foreach ($ledger as $section) {
            foreach ($section['lines'] as $line) {
                $rows[] = [
                    $section['account']->code . ' ' . $section['account']->name,
                    $line['date'],
                    $line['journal_number'],
                    $line['reference'],
                    $line['description'],
                    $line['debit'],
                    $line['credit'],
                    $line['running_balance'],
                ];
            }
        }

        return $rows;
    }
}

<?php

namespace Modules\DoubleEntry\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\DoubleEntry\Models\Account;
use Modules\DoubleEntry\Services\AccountBalanceService;

class ProfitLoss extends Controller
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
        $comparative = $request->boolean('comparative', false);

        $data = $this->buildProfitLoss($companyId, $dateFrom, $dateTo, $basis);

        $priorData = null;
        if ($comparative) {
            $priorFrom = now()->parse($dateFrom)->subYear()->toDateString();
            $priorTo = now()->parse($dateTo)->subYear()->toDateString();
            $priorData = $this->buildProfitLoss($companyId, $priorFrom, $priorTo, $basis);
        }

        return $this->response('double-entry::profit-loss.index', compact(
            'data', 'priorData', 'dateFrom', 'dateTo', 'basis', 'comparative'
        ));
    }

    public function export(Request $request): mixed
    {
        $companyId = company_id();
        $dateFrom = $request->get('date_from', now()->startOfYear()->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());
        $basis = $request->get('basis', 'accrual');
        $format = $request->get('format', 'csv');

        $data = $this->buildProfitLoss($companyId, $dateFrom, $dateTo, $basis);

        if ($format === 'pdf') {
            $comparative = false;
            $priorData = null;
            $pdf = app('dompdf.wrapper');
            $pdf->loadView('double-entry::profit-loss.pdf', compact('data', 'priorData', 'dateFrom', 'dateTo', 'basis', 'comparative'));

            return $pdf->download('profit-loss.pdf');
        }

        $filename = 'profit-loss-' . $dateFrom . '-to-' . $dateTo . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($data) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Account Code', 'Account Name', 'Amount', '% of Income']);

            fputcsv($handle, ['', 'INCOME', '', '']);
            foreach ($data['income'] as $row) {
                fputcsv($handle, [
                    $row['account']->code,
                    $row['account']->name,
                    number_format($row['balance'], 2),
                    $data['total_income'] > 0 ? number_format(($row['balance'] / $data['total_income']) * 100, 1) . '%' : '0%',
                ]);
            }
            fputcsv($handle, ['', 'Total Income', number_format($data['total_income'], 2), '100%']);

            fputcsv($handle, ['', 'EXPENSES', '', '']);
            foreach ($data['expenses'] as $row) {
                fputcsv($handle, [
                    $row['account']->code,
                    $row['account']->name,
                    number_format($row['balance'], 2),
                    $data['total_income'] > 0 ? number_format(($row['balance'] / $data['total_income']) * 100, 1) . '%' : '0%',
                ]);
            }
            fputcsv($handle, ['', 'Total Expenses', number_format($data['total_expenses'], 2), '']);
            fputcsv($handle, ['', 'Net Income', number_format($data['net_income'], 2), '']);

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function buildProfitLoss(int $companyId, string $dateFrom, string $dateTo, string $basis): array
    {
        $incomeAccounts = Account::where('company_id', $companyId)
            ->enabled()
            ->where('type', 'income')
            ->orderBy('code')
            ->get();

        $expenseAccounts = Account::where('company_id', $companyId)
            ->enabled()
            ->where('type', 'expense')
            ->orderBy('code')
            ->get();

        $income = [];
        $totalIncome = 0;

        foreach ($incomeAccounts as $account) {
            $balance = $this->getAccountPeriodBalance($account, $dateFrom, $dateTo, $basis);
            if (abs($balance) >= 0.005) {
                $income[] = ['account' => $account, 'balance' => $balance];
                $totalIncome += $balance;
            }
        }

        $expenses = [];
        $totalExpenses = 0;

        foreach ($expenseAccounts as $account) {
            $balance = $this->getAccountPeriodBalance($account, $dateFrom, $dateTo, $basis);
            if (abs($balance) >= 0.005) {
                $expenses[] = ['account' => $account, 'balance' => $balance];
                $totalExpenses += $balance;
            }
        }

        $netIncome = $totalIncome - $totalExpenses;

        return [
            'income' => $income,
            'expenses' => $expenses,
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_income' => $netIncome,
        ];
    }

    /**
     * Get account balance for a specific period (not cumulative from opening).
     */
    protected function getAccountPeriodBalance(Account $account, string $dateFrom, string $dateTo, string $basis): float
    {
        $query = $account->id;

        $totals = \Modules\DoubleEntry\Models\JournalLine::where('account_id', $account->id)
            ->join('double_entry_journals', 'double_entry_journals.id', '=', 'double_entry_journal_lines.journal_id')
            ->where('double_entry_journals.company_id', $account->company_id)
            ->where('double_entry_journals.status', 'posted')
            ->where('double_entry_journals.basis', $basis)
            ->where('double_entry_journals.date', '>=', $dateFrom)
            ->where('double_entry_journals.date', '<=', $dateTo)
            ->selectRaw('COALESCE(SUM(double_entry_journal_lines.debit), 0) as total_debit, COALESCE(SUM(double_entry_journal_lines.credit), 0) as total_credit')
            ->first();

        if (AccountBalanceService::normalBalanceSide($account->type) === 'debit') {
            return (float) $totals->total_debit - (float) $totals->total_credit;
        }

        return (float) $totals->total_credit - (float) $totals->total_debit;
    }
}

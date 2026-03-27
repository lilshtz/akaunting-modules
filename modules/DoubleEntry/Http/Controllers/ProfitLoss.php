<?php

namespace Modules\DoubleEntry\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Modules\DoubleEntry\Models\Account;
use Modules\DoubleEntry\Models\JournalLine;
use Modules\DoubleEntry\Services\AccountBalanceService;

class ProfitLoss extends Controller
{
    protected AccountBalanceService $balanceService;

    public function __construct(AccountBalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    public function index(Request $request): Response
    {
        $companyId = company_id();
        $dateFrom = $request->get('date_from', now()->startOfYear()->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());
        $basis = $request->get('basis', 'accrual');
        $comparative = $request->boolean('comparative', false);
        $breakdown = $request->get('breakdown', 'none'); // none, monthly, quarterly, annual

        $data = $this->buildProfitLoss($companyId, $dateFrom, $dateTo, $basis);

        $priorData = null;
        if ($comparative) {
            $priorFrom = Carbon::parse($dateFrom)->subYear()->toDateString();
            $priorTo = Carbon::parse($dateTo)->subYear()->toDateString();
            $priorData = $this->buildProfitLoss($companyId, $priorFrom, $priorTo, $basis);
        }

        // Build period breakdown columns
        $periods = [];
        if ($breakdown !== 'none') {
            $periods = $this->buildPeriodBreakdown($companyId, $dateFrom, $dateTo, $basis, $breakdown);
        }

        return $this->response('double-entry::profit-loss.index', compact(
            'data', 'priorData', 'dateFrom', 'dateTo', 'basis', 'comparative', 'breakdown', 'periods'
        ));
    }

    public function export(Request $request): \Illuminate\Http\Response
    {
        $companyId = company_id();
        $dateFrom = $request->get('date_from', now()->startOfYear()->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());
        $basis = $request->get('basis', 'accrual');
        $format = $request->get('format', 'csv');
        $comparative = $request->boolean('comparative', false);

        $data = $this->buildProfitLoss($companyId, $dateFrom, $dateTo, $basis);

        $priorData = null;
        if ($comparative) {
            $priorFrom = Carbon::parse($dateFrom)->subYear()->toDateString();
            $priorTo = Carbon::parse($dateTo)->subYear()->toDateString();
            $priorData = $this->buildProfitLoss($companyId, $priorFrom, $priorTo, $basis);
        }

        if ($format === 'pdf') {
            $pdf = app('dompdf.wrapper');
            $pdf->loadView('double-entry::profit-loss.pdf', compact('data', 'priorData', 'dateFrom', 'dateTo', 'basis', 'comparative'));

            return $pdf->download('profit-loss.pdf');
        }

        $filename = 'profit-loss-' . $dateFrom . '-to-' . $dateTo . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($data, $priorData, $comparative) {
            $handle = fopen('php://output', 'w');

            $header = ['Account Code', 'Account Name', 'Amount', '% of Income'];
            if ($comparative && $priorData) {
                array_splice($header, 3, 0, ['Prior Period']);
            }
            fputcsv($handle, $header);

            fputcsv($handle, ['', 'INCOME', '', '']);
            foreach ($data['income'] as $row) {
                $line = [
                    $row['account']->code,
                    $row['account']->name,
                    number_format($row['balance'], 2),
                ];
                if ($comparative && $priorData) {
                    $priorRow = collect($priorData['income'])->firstWhere('account.id', $row['account']->id);
                    $line[] = $priorRow ? number_format($priorRow['balance'], 2) : '-';
                }
                $line[] = $data['total_income'] > 0 ? number_format(($row['balance'] / $data['total_income']) * 100, 1) . '%' : '0%';
                fputcsv($handle, $line);
            }
            fputcsv($handle, ['', 'Total Income', number_format($data['total_income'], 2), '100%']);

            // Gross Profit line if COGS exists
            if ($data['has_cogs']) {
                fputcsv($handle, ['', 'Cost of Goods Sold', number_format($data['total_cogs'], 2), '']);
                fputcsv($handle, ['', 'Gross Profit', number_format($data['gross_profit'], 2), '']);
            }

            fputcsv($handle, ['', 'EXPENSES', '', '']);
            foreach ($data['expenses'] as $row) {
                $line = [
                    $row['account']->code,
                    $row['account']->name,
                    number_format($row['balance'], 2),
                ];
                if ($comparative && $priorData) {
                    $priorRow = collect($priorData['expenses'])->firstWhere('account.id', $row['account']->id);
                    $line[] = $priorRow ? number_format($priorRow['balance'], 2) : '-';
                }
                $line[] = $data['total_income'] > 0 ? number_format(($row['balance'] / $data['total_income']) * 100, 1) . '%' : '0%';
                fputcsv($handle, $line);
            }
            fputcsv($handle, ['', 'Total Expenses', number_format($data['total_expenses'], 2), '']);
            fputcsv($handle, ['', $data['net_income'] >= 0 ? 'Net Profit' : 'Net Loss', number_format($data['net_income'], 2), '']);

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

        // Detect COGS accounts (accounts with "cost of goods" or "cogs" in name)
        $cogs = [];
        $totalCogs = 0;
        $expenses = [];
        $totalExpenses = 0;

        foreach ($expenseAccounts as $account) {
            $balance = $this->getAccountPeriodBalance($account, $dateFrom, $dateTo, $basis);
            if (abs($balance) < 0.005) {
                continue;
            }

            $isCogs = preg_match('/\b(cogs|cost of (goods|sales))\b/i', $account->name);
            if ($isCogs) {
                $cogs[] = ['account' => $account, 'balance' => $balance];
                $totalCogs += $balance;
            } else {
                $expenses[] = ['account' => $account, 'balance' => $balance];
                $totalExpenses += $balance;
            }
        }

        $hasCogs = !empty($cogs);
        $grossProfit = $totalIncome - $totalCogs;
        $netIncome = $totalIncome - $totalCogs - $totalExpenses;

        return [
            'income' => $income,
            'cogs' => $cogs,
            'expenses' => $expenses,
            'total_income' => $totalIncome,
            'total_cogs' => $totalCogs,
            'has_cogs' => $hasCogs,
            'gross_profit' => $grossProfit,
            'total_expenses' => $totalExpenses,
            'net_income' => $netIncome,
        ];
    }

    /**
     * Build period breakdown data (monthly, quarterly, or annual).
     */
    protected function buildPeriodBreakdown(int $companyId, string $dateFrom, string $dateTo, string $basis, string $breakdown): array
    {
        $periods = [];
        $start = Carbon::parse($dateFrom);
        $end = Carbon::parse($dateTo);

        $intervals = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            switch ($breakdown) {
                case 'monthly':
                    $periodStart = $cursor->copy()->startOfMonth();
                    $periodEnd = $cursor->copy()->endOfMonth();
                    $label = $cursor->format('M Y');
                    $cursor->addMonth();
                    break;
                case 'quarterly':
                    $periodStart = $cursor->copy()->firstOfQuarter();
                    $periodEnd = $cursor->copy()->lastOfQuarter();
                    $label = 'Q' . $cursor->quarter . ' ' . $cursor->year;
                    $cursor->addQuarter();
                    break;
                case 'annual':
                    $periodStart = $cursor->copy()->startOfYear();
                    $periodEnd = $cursor->copy()->endOfYear();
                    $label = $cursor->format('Y');
                    $cursor->addYear();
                    break;
                default:
                    return [];
            }

            // Clamp to the report's date range
            if ($periodStart->lt($start)) {
                $periodStart = $start->copy();
            }
            if ($periodEnd->gt($end)) {
                $periodEnd = $end->copy();
            }

            $intervals[] = [
                'label' => $label,
                'from' => $periodStart->toDateString(),
                'to' => $periodEnd->toDateString(),
            ];
        }

        // Build P&L for each period
        foreach ($intervals as $interval) {
            $periods[] = [
                'label' => $interval['label'],
                'data' => $this->buildProfitLoss($companyId, $interval['from'], $interval['to'], $basis),
            ];
        }

        return $periods;
    }

    /**
     * Get account balance for a specific period (not cumulative from opening).
     */
    protected function getAccountPeriodBalance(Account $account, string $dateFrom, string $dateTo, string $basis): float
    {
        $totals = JournalLine::where('account_id', $account->id)
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

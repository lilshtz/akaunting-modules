<?php

namespace Modules\DoubleEntry\Widgets;

use App\Abstracts\Widget;
use Illuminate\Support\Carbon;
use Modules\DoubleEntry\Models\Account;
use Modules\DoubleEntry\Models\Journal;
use Modules\DoubleEntry\Models\JournalLine;
use Modules\DoubleEntry\Services\AccountBalanceService;

class DoubleEntryDashboard extends Widget
{
    public $default_name = 'Double-Entry Overview';

    public $default_settings = [
        'width' => 'w-full',
    ];

    public function show()
    {
        $companyId = company_id();

        // Income vs Expense chart data (12 months)
        $chartData = $this->getMonthlyChart($companyId);

        // Top 5 accounts by balance
        $topAccounts = $this->getTopAccounts($companyId);

        // Recent journal entries (last 10)
        $recentEntries = Journal::where('company_id', $companyId)
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        return $this->view('double-entry::widgets.dashboard', compact(
            'chartData', 'topAccounts', 'recentEntries'
        ));
    }

    protected function getMonthlyChart(int $companyId): array
    {
        $months = [];
        $income = [];
        $expenses = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth()->toDateString();
            $monthEnd = $date->copy()->endOfMonth()->toDateString();
            $months[] = $date->format('M Y');

            $incomeTotal = JournalLine::join('double_entry_journals', 'double_entry_journals.id', '=', 'double_entry_journal_lines.journal_id')
                ->join('double_entry_accounts', 'double_entry_accounts.id', '=', 'double_entry_journal_lines.account_id')
                ->where('double_entry_journals.company_id', $companyId)
                ->where('double_entry_journals.status', 'posted')
                ->where('double_entry_accounts.type', 'income')
                ->whereBetween('double_entry_journals.date', [$monthStart, $monthEnd])
                ->sum('double_entry_journal_lines.credit');

            $expenseTotal = JournalLine::join('double_entry_journals', 'double_entry_journals.id', '=', 'double_entry_journal_lines.journal_id')
                ->join('double_entry_accounts', 'double_entry_accounts.id', '=', 'double_entry_journal_lines.account_id')
                ->where('double_entry_journals.company_id', $companyId)
                ->where('double_entry_journals.status', 'posted')
                ->where('double_entry_accounts.type', 'expense')
                ->whereBetween('double_entry_journals.date', [$monthStart, $monthEnd])
                ->sum('double_entry_journal_lines.debit');

            $income[] = (float) $incomeTotal;
            $expenses[] = (float) $expenseTotal;
        }

        return [
            'months' => $months,
            'income' => $income,
            'expenses' => $expenses,
        ];
    }

    protected function getTopAccounts(int $companyId): array
    {
        $service = app(AccountBalanceService::class);
        $accounts = Account::where('company_id', $companyId)
            ->enabled()
            ->orderBy('code')
            ->get();

        $balances = [];
        foreach ($accounts as $account) {
            $balance = $service->getBalance($account->id, now()->toDateString(), 'accrual');
            if (abs($balance) >= 0.01) {
                $balances[] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                    'balance' => $balance,
                ];
            }
        }

        // Sort by absolute balance descending, take top 5
        usort($balances, fn($a, $b) => abs($b['balance']) <=> abs($a['balance']));

        return array_slice($balances, 0, 5);
    }
}

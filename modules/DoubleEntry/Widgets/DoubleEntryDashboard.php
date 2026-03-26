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
        $service = app(AccountBalanceService::class);

        // Income vs Expense chart data (12 months)
        $chartData = $this->getMonthlyChart($companyId);

        // Top 5 accounts by activity
        $topAccounts = $this->getTopAccounts($companyId);

        // Recent journal entries
        $recentEntries = Journal::where('company_id', $companyId)
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(5)
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
        return JournalLine::join('double_entry_journals', 'double_entry_journals.id', '=', 'double_entry_journal_lines.journal_id')
            ->join('double_entry_accounts', 'double_entry_accounts.id', '=', 'double_entry_journal_lines.account_id')
            ->where('double_entry_journals.company_id', $companyId)
            ->where('double_entry_journals.status', 'posted')
            ->where('double_entry_journals.date', '>=', now()->subMonths(3)->toDateString())
            ->groupBy('double_entry_journal_lines.account_id', 'double_entry_accounts.code', 'double_entry_accounts.name')
            ->selectRaw('double_entry_journal_lines.account_id, double_entry_accounts.code, double_entry_accounts.name, SUM(double_entry_journal_lines.debit + double_entry_journal_lines.credit) as total_activity')
            ->orderByDesc('total_activity')
            ->limit(5)
            ->get()
            ->toArray();
    }
}

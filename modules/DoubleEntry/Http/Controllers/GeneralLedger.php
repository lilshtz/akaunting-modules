<?php

namespace Modules\DoubleEntry\Http\Controllers;

use App\Abstracts\Http\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\DoubleEntry\Http\Controllers\Concerns\ExportsCsv;
use Modules\DoubleEntry\Models\Account;
use Modules\DoubleEntry\Services\AccountBalanceService;

class GeneralLedger extends Controller
{
    use ExportsCsv;

    public function __construct(protected AccountBalanceService $balances)
    {
    }

    public function index(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $accountId = $request->integer('account_id') ?: null;

        $accounts = Account::query()
            ->byCompany()
            ->where('enabled', true)
            ->orderBy('code')
            ->get();

        $entries = $this->journalLineQuery($accountId, $startDate, $endDate)->get();
        $groupedEntries = $this->buildLedgerGroups($entries, $accountId, $startDate);

        if ($request->query('export') === 'csv') {
            return $this->streamCsv(
                'general-ledger',
                ['Account Code', 'Account Name', 'Date', 'Reference', 'Description', 'Debit', 'Credit', 'Running Balance'],
                $this->generalLedgerCsvRows($groupedEntries),
                $endDate ?: now()->toDateString()
            );
        }

        return view('double-entry::general-ledger.index', [
            'accounts' => $accounts,
            'selectedAccountId' => $accountId,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'groupedEntries' => $groupedEntries,
        ]);
    }

    protected function journalLineQuery(?int $accountId, ?string $startDate, ?string $endDate)
    {
        return DB::table('double_entry_journal_lines')
            ->join('double_entry_journals', 'double_entry_journals.id', '=', 'double_entry_journal_lines.journal_id')
            ->join('double_entry_accounts', 'double_entry_accounts.id', '=', 'double_entry_journal_lines.account_id')
            ->where('double_entry_journals.company_id', company_id())
            ->where('double_entry_accounts.company_id', company_id())
            ->where('double_entry_journals.status', 'posted')
            ->whereNull('double_entry_journals.deleted_at')
            ->whereNull('double_entry_accounts.deleted_at')
            ->when($accountId, fn ($query) => $query->where('double_entry_journal_lines.account_id', $accountId))
            ->when($startDate, fn ($query) => $query->whereDate('double_entry_journals.date', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('double_entry_journals.date', '<=', $endDate))
            ->orderBy('double_entry_accounts.code')
            ->orderBy('double_entry_journals.date')
            ->orderBy('double_entry_journals.id')
            ->orderBy('double_entry_journal_lines.id')
            ->select([
                'double_entry_accounts.id as account_id',
                'double_entry_accounts.code as account_code',
                'double_entry_accounts.name as account_name',
                'double_entry_accounts.type as account_type',
                'double_entry_journals.date as journal_date',
                'double_entry_journals.reference',
                'double_entry_journals.description as journal_description',
                'double_entry_journal_lines.debit',
                'double_entry_journal_lines.credit',
                'double_entry_journal_lines.description as line_description',
            ]);
    }

    protected function buildLedgerGroups(Collection $entries, ?int $accountId, ?string $startDate): array
    {
        $groups = [];

        foreach ($entries->groupBy('account_id') as $entriesByAccount) {
            $first = $entriesByAccount->first();
            $openingBalance = $this->openingBalanceBeforeStart((int) $first->account_id, $startDate);
            $runningBalance = $openingBalance;
            $rows = [];
            $debitTotal = 0.0;
            $creditTotal = 0.0;

            foreach ($entriesByAccount as $entry) {
                $movement = AccountBalanceService::normalBalanceSide($entry->account_type) === 'debit'
                    ? ((float) $entry->debit - (float) $entry->credit)
                    : ((float) $entry->credit - (float) $entry->debit);

                $runningBalance = round($runningBalance + $movement, 4);
                $debitTotal += (float) $entry->debit;
                $creditTotal += (float) $entry->credit;

                $rows[] = [
                    'date' => $entry->journal_date,
                    'reference' => $entry->reference,
                    'description' => $entry->line_description ?: $entry->journal_description,
                    'debit' => round((float) $entry->debit, 4),
                    'credit' => round((float) $entry->credit, 4),
                    'running_balance' => $runningBalance,
                ];
            }

            $groups[] = [
                'account' => [
                    'id' => (int) $first->account_id,
                    'code' => $first->account_code,
                    'name' => $first->account_name,
                    'type' => $first->account_type,
                ],
                'opening_balance' => round($openingBalance, 4),
                'rows' => $rows,
                'subtotal_debit' => round($debitTotal, 4),
                'subtotal_credit' => round($creditTotal, 4),
                'closing_balance' => round($runningBalance, 4),
            ];
        }

        if ($accountId && empty($groups)) {
            $account = Account::query()->byCompany()->find($accountId);

            if ($account) {
                $groups[] = [
                    'account' => [
                        'id' => $account->id,
                        'code' => $account->code,
                        'name' => $account->name,
                        'type' => $account->type,
                    ],
                    'opening_balance' => round($this->openingBalanceBeforeStart($account->id, $startDate), 4),
                    'rows' => [],
                    'subtotal_debit' => 0.0,
                    'subtotal_credit' => 0.0,
                    'closing_balance' => round($this->balances->getAccountBalance($account->id, null, $startDate ? Carbon::parse($startDate)->subDay()->toDateString() : null), 4),
                ];
            }
        }

        return $groups;
    }

    protected function openingBalanceBeforeStart(int $accountId, ?string $startDate): float
    {
        $account = Account::query()->byCompany()->find($accountId);

        if (! $account) {
            return 0.0;
        }

        if (! $startDate) {
            return round((float) $account->opening_balance, 4);
        }

        return $this->balances->getAccountBalance(
            $accountId,
            null,
            Carbon::parse($startDate)->subDay()->toDateString()
        );
    }

    protected function generalLedgerCsvRows(array $groupedEntries): array
    {
        $rows = [];

        foreach ($groupedEntries as $group) {
            foreach ($group['rows'] as $row) {
                $rows[] = [
                    $group['account']['code'],
                    $group['account']['name'],
                    $row['date'],
                    $row['reference'],
                    $row['description'],
                    number_format($row['debit'], 4, '.', ''),
                    number_format($row['credit'], 4, '.', ''),
                    number_format($row['running_balance'], 4, '.', ''),
                ];
            }
        }

        return $rows;
    }
}

<?php

namespace Modules\Payroll\Services;

use Illuminate\Support\Facades\DB;
use Modules\DoubleEntry\Models\Account;
use Modules\DoubleEntry\Models\Journal;
use Modules\Payroll\Models\PayrollRun;

class PayrollJournalService
{
    public function post(PayrollRun $run): Journal
    {
        $expenseAccount = $this->resolveAccount(
            setting('payroll.salary_expense_account_id'),
            'expense'
        );
        $bankAccount = $this->resolveAccount(
            setting('payroll.bank_account_id'),
            'asset'
        );
        $deductionAccount = $this->resolveAccount(
            setting('payroll.deduction_account_id'),
            'liability'
        );

        return DB::transaction(function () use ($run, $expenseAccount, $bankAccount, $deductionAccount) {
            $journal = Journal::create([
                'company_id' => $run->company_id,
                'date' => $run->period_end->toDateString(),
                'reference' => 'PAYRUN-' . $run->id,
                'description' => 'Payroll run #' . $run->id . ' for ' . $run->calendar?->name,
                'basis' => 'accrual',
                'status' => 'posted',
                'documentable_type' => PayrollRun::class,
                'documentable_id' => $run->id,
                'created_by' => auth()->id(),
            ]);

            $journal->lines()->create([
                'account_id' => $expenseAccount->id,
                'debit' => $run->total_gross,
                'credit' => 0,
                'description' => 'Payroll gross expense',
            ]);

            if ($run->total_deductions > 0) {
                $journal->lines()->create([
                    'account_id' => $deductionAccount->id,
                    'debit' => 0,
                    'credit' => $run->total_deductions,
                    'description' => 'Payroll deductions payable',
                ]);
            }

            $journal->lines()->create([
                'account_id' => $bankAccount->id,
                'debit' => 0,
                'credit' => $run->total_net,
                'description' => 'Payroll funding',
            ]);

            return $journal;
        });
    }

    protected function resolveAccount(mixed $accountId, string $type): Account
    {
        $query = Account::where('company_id', company_id())->enabled();

        if ($accountId) {
            $account = (clone $query)->whereKey($accountId)->first();

            if ($account) {
                return $account;
            }
        }

        return $query->where('type', $type)->orderBy('code')->firstOrFail();
    }
}

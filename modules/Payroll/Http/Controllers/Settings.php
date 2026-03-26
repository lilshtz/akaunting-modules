<?php

namespace Modules\Payroll\Http\Controllers;

use App\Abstracts\Http\Controller;
use Modules\DoubleEntry\Models\Account;
use Modules\Payroll\Http\Requests\SettingsUpdate;
use Modules\Payroll\Models\PayItem;

class Settings extends Controller
{
    public function index()
    {
        $benefitItems = PayItem::where('company_id', company_id())->type('benefit')->enabled()->orderBy('name')->get();
        $deductionItems = PayItem::where('company_id', company_id())->type('deduction')->enabled()->orderBy('name')->get();
        $accounts = Account::where('company_id', company_id())->enabled()->orderBy('code')->get();

        $selectedBenefitItems = json_decode((string) setting('payroll.default_benefit_items', '[]'), true) ?: [];
        $selectedDeductionItems = json_decode((string) setting('payroll.default_deduction_items', '[]'), true) ?: [];

        return view('payroll::settings.index', compact(
            'benefitItems',
            'deductionItems',
            'accounts',
            'selectedBenefitItems',
            'selectedDeductionItems'
        ));
    }

    public function update(SettingsUpdate $request)
    {
        setting([
            'payroll.default_benefit_items' => json_encode(array_values($request->get('default_benefit_items', []))),
            'payroll.default_deduction_items' => json_encode(array_values($request->get('default_deduction_items', []))),
            'payroll.salary_expense_account_id' => $request->get('salary_expense_account_id'),
            'payroll.bank_account_id' => $request->get('bank_account_id'),
            'payroll.deduction_account_id' => $request->get('deduction_account_id'),
            'payroll.hours_per_week' => $request->get('hours_per_week', 40),
        ]);

        setting()->save();

        flash(trans('messages.success.updated', ['type' => trans('payroll::general.settings')]))->success();

        return redirect()->route('payroll.settings.index');
    }
}

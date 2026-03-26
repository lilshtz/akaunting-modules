<x-layouts.admin>
    <x-slot name="title">{{ trans('payroll::general.settings') }}</x-slot>

    <x-slot name="content">
        <div class="max-w-4xl bg-white rounded-xl shadow-sm p-6">
            <form method="POST" action="{{ route('payroll.settings.update') }}" class="space-y-6">
                @csrf

                <div>
                    <h3 class="text-lg font-semibold mb-2">{{ trans('payroll::general.default_benefits') }}</h3>
                    <p class="text-sm text-gray-500 mb-3">{{ trans('payroll::general.default_items_help') }}</p>
                    <div class="grid gap-2 md:grid-cols-2">
                        @foreach ($benefitItems as $item)
                            <label class="flex items-center gap-2 p-3 border rounded-lg">
                                <input type="checkbox" name="default_benefit_items[]" value="{{ $item->id }}" @checked(in_array($item->id, old('default_benefit_items', $selectedBenefitItems), true)) />
                                <span>{{ $item->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-semibold mb-2">{{ trans('payroll::general.default_deductions') }}</h3>
                    <div class="grid gap-2 md:grid-cols-2">
                        @foreach ($deductionItems as $item)
                            <label class="flex items-center gap-2 p-3 border rounded-lg">
                                <input type="checkbox" name="default_deduction_items[]" value="{{ $item->id }}" @checked(in_array($item->id, old('default_deduction_items', $selectedDeductionItems), true)) />
                                <span>{{ $item->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('payroll::general.salary_expense_account') }}</label>
                        <select name="salary_expense_account_id" class="w-full rounded-lg border-gray-300">
                            <option value=""></option>
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}" @selected((string) old('salary_expense_account_id', setting('payroll.salary_expense_account_id')) === (string) $account->id)>{{ $account->display_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('payroll::general.bank_account') }}</label>
                        <select name="bank_account_id" class="w-full rounded-lg border-gray-300">
                            <option value=""></option>
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}" @selected((string) old('bank_account_id', setting('payroll.bank_account_id')) === (string) $account->id)>{{ $account->display_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('payroll::general.deduction_account') }}</label>
                        <select name="deduction_account_id" class="w-full rounded-lg border-gray-300">
                            <option value=""></option>
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}" @selected((string) old('deduction_account_id', setting('payroll.deduction_account_id')) === (string) $account->id)>{{ $account->display_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('payroll::general.hours_per_week') }}</label>
                        <input type="number" step="0.01" min="1" name="hours_per_week" value="{{ old('hours_per_week', setting('payroll.hours_per_week', 40)) }}" class="w-full rounded-lg border-gray-300" />
                    </div>
                </div>

                <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg">{{ trans('general.save') }}</button>
            </form>
        </div>
    </x-slot>
</x-layouts.admin>

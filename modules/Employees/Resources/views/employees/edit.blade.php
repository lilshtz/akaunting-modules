<x-layouts.admin>
    <x-slot name="title">
        {{ trans('general.title.edit', ['type' => trans('employees::general.employee')]) }}
    </x-slot>

    <x-slot name="content">
        <x-form.container>
            <x-form id="employee" method="PATCH" :route="['employees.employees.update', $employee->id]" :model="$employee" :files="true">
                {{-- Personal Information --}}
                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head title="{{ trans('employees::general.personal_info') }}" />
                    </x-slot>

                    <x-slot name="body">
                        <x-form.group.select name="contact_id" label="{{ trans('employees::general.contact') }}" :options="$contacts" :value="$employee->contact_id" />

                        <x-form.group.file name="photo" label="{{ trans('employees::general.photo') }}" not-required />

                        <x-form.group.date name="birthday" label="{{ trans('employees::general.birthday') }}" :value="$employee->birthday?->format('Y-m-d')" not-required />
                    </x-slot>
                </x-form.section>

                {{-- Employment Information --}}
                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head title="{{ trans('employees::general.employment_info') }}" />
                    </x-slot>

                    <x-slot name="body">
                        <x-form.group.select name="department_id" label="{{ trans('employees::general.department') }}" :options="$departments" :value="$employee->department_id" not-required />

                        <x-form.group.select name="type" label="{{ trans('employees::general.employee_type') }}" :options="$types" :value="$employee->type" />

                        <x-form.group.select name="classification" label="{{ trans('employees::general.classification') }}" :options="$classifications" :value="$employee->classification" />

                        <x-form.group.select name="status" label="{{ trans('general.status') }}" :options="$statuses" :value="$employee->status" />

                        <x-form.group.date name="hire_date" label="{{ trans('employees::general.hire_date') }}" :value="$employee->hire_date?->format('Y-m-d')" not-required />

                        @if ($employee->status === 'terminated')
                            <x-form.group.date name="terminated_at" label="{{ trans('employees::general.terminated_at') }}" :value="$employee->terminated_at?->format('Y-m-d')" not-required />
                        @endif

                        <x-form.group.textarea name="notes" label="{{ trans('employees::general.notes') }}" :value="$employee->notes" not-required />
                    </x-slot>
                </x-form.section>

                {{-- Salary & Banking --}}
                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head title="{{ trans('employees::general.salary_info') }}" />
                    </x-slot>

                    <x-slot name="body">
                        <x-form.group.text name="salary" label="{{ trans('employees::general.salary') }}" :value="$employee->salary" not-required />

                        <x-form.group.select name="salary_type" label="{{ trans('employees::general.salary_type') }}" :options="$salaryTypes" :value="$employee->salary_type" not-required />

                        <x-form.group.text name="bank_name" label="{{ trans('employees::general.bank_name') }}" :value="$employee->bank_name" not-required />

                        <x-form.group.text name="bank_account" label="{{ trans('employees::general.bank_account') }}" :value="$employee->bank_account" not-required />

                        <x-form.group.text name="bank_routing" label="{{ trans('employees::general.bank_routing') }}" :value="$employee->bank_routing" not-required />
                    </x-slot>
                </x-form.section>

                <x-form.section>
                    <x-slot name="foot">
                        <x-form.buttons cancel-route="employees.employees.index" />
                    </x-slot>
                </x-form.section>
            </x-form>
        </x-form.container>
    </x-slot>
</x-layouts.admin>

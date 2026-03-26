<x-layouts.admin>
    <x-slot name="title">
        {{ trans('general.title.new', ['type' => trans('employees::general.employee')]) }}
    </x-slot>

    <x-slot name="content">
        <x-form.container>
            <x-form id="employee" method="POST" route="employees.employees.store" :files="true">
                {{-- Personal Information --}}
                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head title="{{ trans('employees::general.personal_info') }}" />
                    </x-slot>

                    <x-slot name="body">
                        <x-form.group.select name="contact_id" label="{{ trans('employees::general.contact') }}" :options="$contacts" not-required />

                        <x-form.group.text name="contact_name" label="{{ trans('employees::general.contact_name') }}" not-required placeholder="Enter name if creating new contact" />

                        <x-form.group.text name="contact_email" label="{{ trans('employees::general.contact_email') }}" not-required />

                        <x-form.group.file name="photo" label="{{ trans('employees::general.photo') }}" not-required />

                        <x-form.group.date name="birthday" label="{{ trans('employees::general.birthday') }}" not-required />
                    </x-slot>
                </x-form.section>

                {{-- Employment Information --}}
                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head title="{{ trans('employees::general.employment_info') }}" />
                    </x-slot>

                    <x-slot name="body">
                        <x-form.group.select name="department_id" label="{{ trans('employees::general.department') }}" :options="$departments" not-required />

                        <x-form.group.select name="type" label="{{ trans('employees::general.employee_type') }}" :options="$types" />

                        <x-form.group.select name="classification" label="{{ trans('employees::general.classification') }}" :options="$classifications" />

                        <x-form.group.date name="hire_date" label="{{ trans('employees::general.hire_date') }}" not-required />

                        <x-form.group.textarea name="notes" label="{{ trans('employees::general.notes') }}" not-required />
                    </x-slot>
                </x-form.section>

                {{-- Salary & Banking --}}
                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head title="{{ trans('employees::general.salary_info') }}" />
                    </x-slot>

                    <x-slot name="body">
                        <x-form.group.text name="salary" label="{{ trans('employees::general.salary') }}" not-required />

                        <x-form.group.select name="salary_type" label="{{ trans('employees::general.salary_type') }}" :options="$salaryTypes" not-required />

                        <x-form.group.text name="bank_name" label="{{ trans('employees::general.bank_name') }}" not-required />

                        <x-form.group.text name="bank_account" label="{{ trans('employees::general.bank_account') }}" not-required />

                        <x-form.group.text name="bank_routing" label="{{ trans('employees::general.bank_routing') }}" not-required />
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

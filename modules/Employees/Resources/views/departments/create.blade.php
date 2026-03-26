<x-layouts.admin>
    <x-slot name="title">
        {{ trans('general.title.new', ['type' => trans('employees::general.department')]) }}
    </x-slot>

    <x-slot name="content">
        <x-form.container>
            <x-form id="department" method="POST" route="employees.departments.store">
                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head title="{{ trans('general.general') }}" />
                    </x-slot>

                    <x-slot name="body">
                        <x-form.group.text name="name" label="{{ trans('general.name') }}" />

                        <x-form.group.textarea name="description" label="{{ trans('general.description') }}" not-required />

                        <x-form.group.select name="manager_id" label="{{ trans('employees::general.manager') }}" :options="$managers" not-required />
                    </x-slot>
                </x-form.section>

                <x-form.section>
                    <x-slot name="foot">
                        <x-form.buttons cancel-route="employees.departments.index" />
                    </x-slot>
                </x-form.section>
            </x-form>
        </x-form.container>
    </x-slot>
</x-layouts.admin>

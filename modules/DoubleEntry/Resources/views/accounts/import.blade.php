<x-layouts.admin>
    <x-slot name="title">
        {{ trans('double-entry::general.import_accounts') }}
    </x-slot>

    <x-slot name="content">
        <x-form.container>
            <x-form id="import" method="POST" route="double-entry.accounts.import.process" enctype="multipart/form-data">
                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head title="{{ trans('double-entry::general.import_accounts') }}" description="{{ trans('double-entry::general.csv_format') }}" />
                    </x-slot>

                    <x-slot name="body">
                        <x-form.group.file name="import" label="{{ trans('double-entry::general.csv_file') }}" />
                    </x-slot>
                </x-form.section>

                <x-form.section>
                    <x-slot name="foot">
                        <x-form.buttons cancel-route="double-entry.accounts.index" />
                    </x-slot>
                </x-form.section>
            </x-form>
        </x-form.container>
    </x-slot>
</x-layouts.admin>

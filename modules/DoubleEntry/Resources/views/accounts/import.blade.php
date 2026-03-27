<x-layouts.admin>
    <x-slot name="title">{{ trans('double-entry::general.import_csv') }}</x-slot>

    <x-slot name="content">
        <x-form.container>
            <x-form id="import-accounts" method="POST" route="double-entry.accounts.store-import" files="true">
                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head title="{{ trans('double-entry::general.import_csv') }}" description="QuickBooks-style CSV columns such as code, name, type, detail type, opening balance." />
                    </x-slot>

                    <x-slot name="body">
                        <x-form.group.file name="file" label="{{ trans('double-entry::general.import_csv') }}" />
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

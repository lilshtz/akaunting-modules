<x-layouts.admin>
    <x-slot name="title">
        {{ trans('general.title.new', ['type' => trans_choice('double-entry::general.accounts', 1)]) }}
    </x-slot>

    <x-slot name="content">
        <x-form.container>
            <x-form id="account" method="POST" route="double-entry.accounts.store">
                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head title="{{ trans('general.general') }}" />
                    </x-slot>

                    <x-slot name="body">
                        <x-form.group.text name="code" label="{{ trans('double-entry::general.account_code') }}" />

                        <x-form.group.text name="name" label="{{ trans('general.name') }}" />

                        <x-form.group.select name="type" label="{{ trans('double-entry::general.account_type') }}" :options="$types" />

                        <x-form.group.select name="parent_id" label="{{ trans('double-entry::general.parent_account') }}" :options="$parents" not-required />

                        <x-form.group.textarea name="description" label="{{ trans('general.description') }}" not-required />

                        <x-form.group.text name="opening_balance" label="{{ trans('double-entry::general.opening_balance') }}" value="0" />

                        <x-form.group.toggle name="enabled" label="{{ trans('general.enabled') }}" :value="true" />
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

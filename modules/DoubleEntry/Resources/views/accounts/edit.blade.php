<x-layouts.admin>
    <x-slot name="title">{{ trans('general.edit') }} {{ $account->name }}</x-slot>

    <x-slot name="content">
        <x-form.container>
            <x-form id="double-entry-account" method="PATCH" :route="['double-entry.accounts.update', $account->id]" :model="$account">
                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head title="{{ trans('double-entry::general.account') }}" />
                    </x-slot>

                    <x-slot name="body">
                        <x-form.group.text name="code" label="{{ trans('double-entry::general.account_code') }}" :value="$account->code" />
                        <x-form.group.text name="name" label="{{ trans('double-entry::general.account') }}" :value="$account->name" />
                        <x-form.group.select name="type" label="{{ trans('double-entry::general.account_type') }}" :options="$types" :selected="$account->type" />
                        <x-form.group.text name="detail_type" label="{{ trans('double-entry::general.detail_type') }}" :value="$account->detail_type" not-required />
                        <x-form.group.select name="parent_id" label="{{ trans('double-entry::general.parent_account') }}" :options="$parents" :selected="$account->parent_id" not-required />
                        <x-form.group.text name="opening_balance" label="{{ trans('double-entry::general.opening_balance') }}" :value="$account->opening_balance" not-required />
                        <x-form.group.textarea name="description" label="{{ trans('general.description') }}" :value="$account->description" not-required />
                        <x-form.group.toggle name="enabled" label="{{ trans('general.enabled') }}" :value="$account->enabled" />
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

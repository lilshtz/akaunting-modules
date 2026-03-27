<x-layouts.admin>
    <x-slot name="title">{{ trans('double-entry::general.account_defaults') }}</x-slot>

    <x-slot name="favorite"
        title="{{ trans('double-entry::general.account_defaults') }}"
        icon="settings"
        route="double-entry.account-defaults.index"
    ></x-slot>

    <x-slot name="content">
        <x-form.container>
            <x-form id="account-defaults" method="POST" route="double-entry.account-defaults.update">
                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head title="{{ trans('double-entry::general.account_defaults') }}" description="{{ trans('double-entry::general.no_defaults') }}" />
                    </x-slot>

                    <x-slot name="body">
                        @foreach ($keys as $key => $label)
                            <x-form.group.select
                                name="defaults[{{ $key }}]"
                                label="{{ $label }}"
                                :options="$accounts"
                                :selected="optional($defaults->get($key))->account_id"
                                not-required
                            />
                        @endforeach
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

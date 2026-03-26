<x-layouts.admin>
    <x-slot name="title">{{ trans('paypal-sync::general.settings') }}</x-slot>

    <x-slot name="favorite"
        title="{{ trans('paypal-sync::general.settings') }}"
        icon="account_balance_wallet"
        route="paypal-sync.settings.edit"
    ></x-slot>

    <x-slot name="content">
        <x-show.container>
            <x-show.content class="flex flex-col lg:flex-row mt-5 sm:mt-12 gap-y-4" override="class">
                <x-show.content.left>
                    <x-form id="paypal-sync-settings" method="POST" route="paypal-sync.settings.update">
                        <x-form.section>
                            <x-slot name="head">
                                <x-form.section.head title="{{ trans('paypal-sync::general.settings') }}" description="{{ trans('paypal-sync::general.description') }}" />
                            </x-slot>

                            <x-slot name="body">
                                <x-form.group.text
                                    name="client_id"
                                    label="{{ trans('paypal-sync::general.client_id') }}"
                                    :value="$settings->client_id"
                                    form-group-class="sm:col-span-6"
                                    type="password"
                                />

                                <x-form.group.text
                                    name="client_secret"
                                    label="{{ trans('paypal-sync::general.client_secret') }}"
                                    :value="$settings->client_secret"
                                    form-group-class="sm:col-span-6"
                                    type="password"
                                />

                                <x-form.group.select
                                    name="mode"
                                    label="{{ trans('paypal-sync::general.mode') }}"
                                    :options="[
                                        'sandbox' => trans('paypal-sync::general.sandbox'),
                                        'live' => trans('paypal-sync::general.live'),
                                    ]"
                                    :selected="$settings->mode"
                                    form-group-class="sm:col-span-6"
                                />

                                <x-form.group.select
                                    name="bank_account_id"
                                    label="{{ trans('paypal-sync::general.bank_account') }}"
                                    :options="$accounts"
                                    :selected="$settings->bank_account_id"
                                    form-group-class="sm:col-span-6"
                                />

                                <x-form.group.toggle
                                    name="enabled"
                                    label="{{ trans('paypal-sync::general.enabled') }}"
                                    :value="$settings->enabled"
                                />

                                <div class="sm:col-span-6">
                                    <label class="text-sm font-medium text-gray-700">{{ trans('paypal-sync::general.last_sync') }}</label>
                                    <p class="mt-1 text-sm text-gray-500">
                                        {{ $settings->last_sync ? $settings->last_sync->format('Y-m-d H:i:s') : trans('paypal-sync::general.never') }}
                                    </p>
                                </div>
                            </x-slot>

                            <x-slot name="foot">
                                <x-form.buttons :cancel="url()->previous()" />
                            </x-slot>
                        </x-form.section>
                    </x-form>
                </x-show.content.left>
            </x-show.content>
        </x-show.container>
    </x-slot>
</x-layouts.admin>

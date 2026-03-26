<x-layouts.admin>
    <x-slot name="title">{{ trans('stripe::general.settings') }}</x-slot>

    <x-slot name="favorite"
        title="{{ trans('stripe::general.settings') }}"
        icon="credit_card"
        route="stripe.settings.edit"
    ></x-slot>

    <x-slot name="content">
        <x-show.container>
            <x-show.content class="flex flex-col-reverse lg:flex-row mt-5 sm:mt-12 gap-y-4" override="class">
                <x-show.content.left>
                    <x-form id="stripe-settings" method="POST" route="stripe.settings.update">
                        <x-form.section>
                            <x-slot name="head">
                                <x-form.section.head title="{{ trans('stripe::general.settings') }}" description="{{ trans('stripe::general.description') }}" />
                            </x-slot>

                            <x-slot name="body">
                                <x-form.group.text
                                    name="api_key"
                                    label="{{ trans('stripe::general.api_key') }}"
                                    :value="$settings->api_key ? '••••••••' : ''"
                                    form-group-class="sm:col-span-6"
                                    type="password"
                                />

                                <x-form.group.text
                                    name="webhook_secret"
                                    label="{{ trans('stripe::general.webhook_secret') }}"
                                    :value="$settings->webhook_secret ? '••••••••' : ''"
                                    form-group-class="sm:col-span-6"
                                    type="password"
                                />

                                <x-form.group.toggle
                                    name="test_mode"
                                    label="{{ trans('stripe::general.test_mode') }}"
                                    :value="$settings->test_mode"
                                />

                                <x-form.group.toggle
                                    name="enabled"
                                    label="{{ trans('stripe::general.enabled') }}"
                                    :value="$settings->enabled"
                                />

                                <div class="sm:col-span-6">
                                    <label class="text-black text-sm font-medium">
                                        {{ trans('stripe::general.webhook_url') }}
                                    </label>
                                    <div class="mt-1">
                                        <input
                                            type="text"
                                            value="{{ $webhookUrl }}"
                                            class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-500"
                                            readonly
                                            onclick="this.select()"
                                        />
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">
                                        Copy this URL into your Stripe Dashboard webhook settings.
                                    </p>
                                </div>
                            </x-slot>

                            <x-slot name="foot">
                                <x-form.buttons :cancel="url()->previous()" />
                            </x-slot>
                        </x-form.section>
                    </x-form>
                </x-show.content.left>

                <x-show.content.right>
                    <x-form.section.head
                        title="{{ trans('stripe::general.payment_history') }}"
                        description="{{ trans('stripe::general.description') }}"
                    />

                    <div class="mt-4">
                        <a href="{{ route('stripe.payments.index') }}"
                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                            {{ trans('stripe::general.payment_history') }}
                        </a>
                    </div>
                </x-show.content.right>
            </x-show.content>
        </x-show.container>
    </x-slot>
</x-layouts.admin>

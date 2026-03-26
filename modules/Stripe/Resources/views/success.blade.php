<x-layouts.admin>
    <x-slot name="title">{{ trans('stripe::general.payment_success') }}</x-slot>

    <x-slot name="content">
        <x-show.container>
            <x-show.content>
                <div class="flex flex-col items-center justify-center py-12">
                    <div class="rounded-full bg-green-100 p-6 mb-6">
                        <span class="material-icons-outlined text-green-600 text-5xl">check_circle</span>
                    </div>

                    <h2 class="text-2xl font-bold text-gray-800 mb-2">
                        {{ trans('stripe::general.payment_success') }}
                    </h2>

                    <p class="text-gray-600 mb-6">
                        {{ trans('documents.invoice_number', ['number' => $invoice->document_number]) }}
                    </p>

                    <div class="bg-white rounded-lg shadow-sm border p-6 w-full max-w-md">
                        <div class="flex justify-between mb-3">
                            <span class="text-gray-500">{{ trans('general.amount') }}</span>
                            <span class="font-semibold">@money($invoice->amount_due, $invoice->currency_code, true)</span>
                        </div>

                        @if($stripePayment)
                            <div class="flex justify-between mb-3">
                                <span class="text-gray-500">{{ trans('general.status') }}</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    {{ trans('stripe::general.status.' . $stripePayment->status) }}
                                </span>
                            </div>

                            @if($stripePayment->stripe_payment_intent_id)
                                <div class="flex justify-between">
                                    <span class="text-gray-500">{{ trans('general.reference') }}</span>
                                    <span class="text-sm font-mono">{{ $stripePayment->stripe_payment_intent_id }}</span>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </x-show.content>
        </x-show.container>
    </x-slot>
</x-layouts.admin>

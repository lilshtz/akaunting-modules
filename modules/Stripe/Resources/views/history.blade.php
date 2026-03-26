<x-layouts.admin>
    <x-slot name="title">{{ trans('stripe::general.payment_history') }}</x-slot>

    <x-slot name="favorite"
        title="{{ trans('stripe::general.payment_history') }}"
        icon="credit_card"
        route="stripe.payments.index"
    ></x-slot>

    <x-slot name="content">
        <x-show.container>
            <x-show.content>
                <x-table>
                    <x-table.thead>
                        <x-table.tr class="flex items-center px-1">
                            <x-table.th class="w-2/12">
                                {{ trans('general.date') }}
                            </x-table.th>

                            <x-table.th class="w-2/12">
                                {{ trans_choice('general.invoices', 1) }}
                            </x-table.th>

                            <x-table.th class="w-2/12">
                                {{ trans('general.amount') }}
                            </x-table.th>

                            <x-table.th class="w-1/12">
                                {{ trans('currencies.code') }}
                            </x-table.th>

                            <x-table.th class="w-1/12">
                                {{ trans('general.status') }}
                            </x-table.th>

                            <x-table.th class="w-2/12">
                                Stripe ID
                            </x-table.th>

                            <x-table.th class="w-2/12">
                                {{ trans('general.actions') }}
                            </x-table.th>
                        </x-table.tr>
                    </x-table.thead>

                    <x-table.tbody>
                        @forelse($payments as $payment)
                            <x-table.tr>
                                <x-table.td class="w-2/12">
                                    {{ \Carbon\Carbon::parse($payment->created_at)->format('Y-m-d H:i') }}
                                </x-table.td>

                                <x-table.td class="w-2/12">
                                    @if($payment->document)
                                        {{ $payment->document->document_number }}
                                    @else
                                        {{ trans('general.na') }}
                                    @endif
                                </x-table.td>

                                <x-table.td class="w-2/12">
                                    @money($payment->amount, $payment->currency, true)
                                </x-table.td>

                                <x-table.td class="w-1/12">
                                    {{ $payment->currency }}
                                </x-table.td>

                                <x-table.td class="w-1/12">
                                    @switch($payment->status)
                                        @case('succeeded')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                {{ trans('stripe::general.status.succeeded') }}
                                            </span>
                                            @break
                                        @case('pending')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                {{ trans('stripe::general.status.pending') }}
                                            </span>
                                            @break
                                        @case('failed')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                {{ trans('stripe::general.status.failed') }}
                                            </span>
                                            @break
                                        @case('refunded')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                {{ trans('stripe::general.status.refunded') }}
                                            </span>
                                            @break
                                    @endswitch
                                </x-table.td>

                                <x-table.td class="w-2/12">
                                    <span class="text-xs font-mono truncate" title="{{ $payment->stripe_payment_intent_id }}">
                                        {{ $payment->stripe_payment_intent_id ?? $payment->stripe_session_id }}
                                    </span>
                                </x-table.td>

                                <x-table.td class="w-2/12">
                                    @if($payment->status === 'succeeded' && $payment->stripe_charge_id)
                                        @can('update-stripe-payments')
                                            <x-form
                                                id="refund-form-{{ $payment->id }}"
                                                method="POST"
                                                :url="route('stripe.payments.refund', $payment->id)"
                                            >
                                                <x-button
                                                    type="submit"
                                                    class="relative bg-white hover:bg-gray-100 border py-0.5 px-2 cursor-pointer text-sm text-red-600"
                                                    override="class"
                                                    onclick="return confirm('{{ trans('stripe::general.refund_confirm') }}')"
                                                >
                                                    {{ trans('stripe::general.refund') }}
                                                </x-button>
                                            </x-form>
                                        @endcan
                                    @elseif($payment->status === 'refunded')
                                        <span class="text-xs text-gray-500">
                                            {{ $payment->refund_id }}
                                        </span>
                                    @endif
                                </x-table.td>
                            </x-table.tr>
                        @empty
                            <x-table.tr>
                                <x-table.td colspan="7">
                                    <div class="flex justify-center py-4 text-gray-500">
                                        {{ trans('general.no_records') }}
                                    </div>
                                </x-table.td>
                            </x-table.tr>
                        @endforelse
                    </x-table.tbody>
                </x-table>

                @if($payments->hasPages())
                    <div class="mt-4">
                        {{ $payments->links() }}
                    </div>
                @endif
            </x-show.content>
        </x-show.container>
    </x-slot>
</x-layouts.admin>

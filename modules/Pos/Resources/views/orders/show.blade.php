<x-layouts.admin>
    <x-slot name="title">
        {{ $order->order_number }}
    </x-slot>

    <div class="grid gap-6 lg:grid-cols-[1.2fr,0.8fr]">
        <section class="rounded-xl bg-white p-6 shadow-sm">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold">{{ $order->order_number }}</h1>
                    <p class="text-sm text-gray-500">{{ $order->status_label }} · {{ company_date($order->created_at) }}</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('pos.orders.receipt', $order->id) }}" target="_blank" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700">{{ trans('pos::general.print_receipt') }}</a>
                    <a href="{{ route('pos.orders.receipt', $order->id) }}" target="_blank" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700">{{ trans('pos::general.download_receipt') }}</a>
                    @if ($order->contact_id && $order->contact->email)
                        <a href="mailto:{{ $order->contact->email }}?subject={{ rawurlencode($order->order_number . ' ' . trans('pos::general.receipt')) }}&body={{ rawurlencode(route('pos.orders.receipt', $order->id)) }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700">{{ trans('pos::general.email_receipt') }}</a>
                    @endif
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">{{ trans('pos::general.products') }}</th>
                            <th class="px-4 py-3 text-right">Qty</th>
                            <th class="px-4 py-3 text-right">{{ trans('pos::general.price') }}</th>
                            <th class="px-4 py-3 text-right">{{ trans('pos::general.discount') }}</th>
                            <th class="px-4 py-3 text-right">{{ trans('pos::general.tax') }}</th>
                            <th class="px-4 py-3 text-right">{{ trans('pos::general.total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($order->items as $item)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $item->name }}</div>
                                    @if ($item->sku)
                                        <div class="text-xs text-gray-500">{{ $item->sku }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">{{ $item->quantity }}</td>
                                <td class="px-4 py-3 text-right">@money($item->price, setting('default.currency', 'USD'), true)</td>
                                <td class="px-4 py-3 text-right">@money($item->discount, setting('default.currency', 'USD'), true)</td>
                                <td class="px-4 py-3 text-right">@money($item->tax, setting('default.currency', 'USD'), true)</td>
                                <td class="px-4 py-3 text-right">@money($item->total, setting('default.currency', 'USD'), true)</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="space-y-6">
            <section class="rounded-xl bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold">{{ trans('pos::general.summary') }}</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span>{{ trans('pos::general.customer') }}</span><strong>{{ $order->contact_id ? $order->contact->name : trans('pos::general.walk_in_customer') }}</strong></div>
                    <div class="flex justify-between"><span>{{ trans('pos::general.default_payment_method') }}</span><strong>{{ $order->payment_method_label }}</strong></div>
                    <div class="flex justify-between"><span>{{ trans('pos::general.subtotal') }}</span><strong>@money($order->subtotal, setting('default.currency', 'USD'), true)</strong></div>
                    <div class="flex justify-between"><span>{{ trans('pos::general.discount') }}</span><strong>@money($order->discount, setting('default.currency', 'USD'), true)</strong></div>
                    <div class="flex justify-between"><span>{{ trans('pos::general.tax') }}</span><strong>@money($order->tax, setting('default.currency', 'USD'), true)</strong></div>
                    <div class="flex justify-between"><span>{{ trans('pos::general.total') }}</span><strong>@money($order->total, setting('default.currency', 'USD'), true)</strong></div>
                    <div class="flex justify-between"><span>{{ trans('pos::general.paid_amount') }}</span><strong>@money($order->paid_amount, setting('default.currency', 'USD'), true)</strong></div>
                    <div class="flex justify-between"><span>{{ trans('pos::general.change_amount') }}</span><strong>@money($order->change_amount, setting('default.currency', 'USD'), true)</strong></div>
                </div>
            </section>

            @if ($order->status !== \Modules\Pos\Models\PosOrder::STATUS_REFUNDED)
                <section class="rounded-xl bg-white p-6 shadow-sm">
                    <h2 class="mb-4 text-lg font-semibold">{{ trans('pos::general.refund_items') }}</h2>
                    <form method="POST" action="{{ route('pos.orders.refund', $order->id) }}" class="space-y-4">
                        @csrf
                        @foreach ($order->items as $index => $item)
                            <div class="rounded-lg border border-gray-200 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="font-medium">{{ $item->name }}</div>
                                        <div class="text-xs text-gray-500">{{ trans('pos::general.total') }}: @money($item->total, setting('default.currency', 'USD'), true)</div>
                                    </div>
                                    <div class="w-28">
                                        <input type="hidden" name="items[{{ $index }}][order_item_id]" value="{{ $item->id }}">
                                        <input type="number" name="items[{{ $index }}][quantity]" min="0" max="{{ $item->quantity }}" step="0.01" class="w-full rounded-lg border border-gray-200 px-3 py-2" value="0">
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <button type="submit" class="w-full rounded-lg bg-red-600 px-4 py-3 text-sm font-semibold text-white">
                            {{ trans('pos::general.refund') }}
                        </button>
                    </form>
                </section>
            @endif
        </aside>
    </div>
</x-layouts.admin>

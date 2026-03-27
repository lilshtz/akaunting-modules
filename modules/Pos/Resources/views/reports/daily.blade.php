<x-layouts.admin>
    <x-slot name="title">
        {{ trans('pos::general.daily_sales_summary') }}
    </x-slot>

    <section class="rounded-xl bg-white p-6 shadow-sm">
        <form method="GET" class="mb-6 flex flex-wrap gap-3">
            <input type="date" name="date" value="{{ $summary['date'] }}" class="rounded-lg border border-gray-200 px-4 py-3">
            <button class="rounded-lg bg-black px-4 py-3 text-sm font-semibold text-white" type="submit">{{ trans('general.filter') }}</button>
        </form>

        <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-6">
            <div class="rounded-xl bg-gray-50 p-4"><div class="text-xs uppercase text-gray-500">{{ trans('pos::general.gross_sales') }}</div><div class="mt-2 text-xl font-semibold">@money($summary['gross_sales'], setting('default.currency', 'USD'), true)</div></div>
            <div class="rounded-xl bg-gray-50 p-4"><div class="text-xs uppercase text-gray-500">{{ trans('pos::general.refund_total') }}</div><div class="mt-2 text-xl font-semibold">@money($summary['refunds'], setting('default.currency', 'USD'), true)</div></div>
            <div class="rounded-xl bg-gray-50 p-4"><div class="text-xs uppercase text-gray-500">{{ trans('pos::general.net_sales') }}</div><div class="mt-2 text-xl font-semibold">@money($summary['net_sales'], setting('default.currency', 'USD'), true)</div></div>
            <div class="rounded-xl bg-gray-50 p-4"><div class="text-xs uppercase text-gray-500">{{ trans('pos::general.tax') }}</div><div class="mt-2 text-xl font-semibold">@money($summary['tax'], setting('default.currency', 'USD'), true)</div></div>
            <div class="rounded-xl bg-gray-50 p-4"><div class="text-xs uppercase text-gray-500">{{ trans('pos::general.discount') }}</div><div class="mt-2 text-xl font-semibold">@money($summary['discount'], setting('default.currency', 'USD'), true)</div></div>
            <div class="rounded-xl bg-gray-50 p-4"><div class="text-xs uppercase text-gray-500">{{ trans('pos::general.order_count') }}</div><div class="mt-2 text-xl font-semibold">{{ $summary['order_count'] }}</div></div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <div class="overflow-hidden rounded-xl border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">{{ trans('pos::general.default_payment_method') }}</th>
                            <th class="px-4 py-3 text-left">{{ trans('general.total') }}</th>
                            <th class="px-4 py-3 text-left">{{ trans('pos::general.total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($paymentSummary as $row)
                            <tr>
                                <td class="px-4 py-3">{{ trans('pos::general.payment_methods.' . $row->payment_method) }}</td>
                                <td class="px-4 py-3">{{ $row->order_count }}</td>
                                <td class="px-4 py-3">@money($row->total_amount, setting('default.currency', 'USD'), true)</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">#</th>
                            <th class="px-4 py-3 text-left">{{ trans('pos::general.customer') }}</th>
                            <th class="px-4 py-3 text-left">{{ trans('pos::general.total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($orders as $order)
                            <tr>
                                <td class="px-4 py-3"><a class="text-emerald-700 hover:underline" href="{{ route('pos.orders.show', $order->id) }}">{{ $order->order_number }}</a></td>
                                <td class="px-4 py-3">{{ $order->contact_id ? $order->contact->name : trans('pos::general.walk_in_customer') }}</td>
                                <td class="px-4 py-3">@money($order->total, setting('default.currency', 'USD'), true)</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-gray-500">{{ trans('general.no_records') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</x-layouts.admin>

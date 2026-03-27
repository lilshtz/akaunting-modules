<x-layouts.admin>
    <x-slot name="title">
        {{ trans('pos::general.order_history') }}
    </x-slot>

    <section class="rounded-xl bg-white p-6 shadow-sm">
        <form method="GET" class="mb-6 grid gap-4 md:grid-cols-[2fr,1fr,auto]">
            <input type="text" name="search" value="{{ request('search') }}" class="rounded-lg border border-gray-200 px-4 py-3" placeholder="{{ trans('pos::general.search_orders') }}">
            <select name="status" class="rounded-lg border border-gray-200 px-4 py-3">
                <option value="">{{ trans('general.all') }}</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="rounded-lg bg-black px-4 py-3 text-sm font-semibold text-white" type="submit">{{ trans('general.filter') }}</button>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">#</th>
                        <th class="px-4 py-3 text-left">{{ trans('pos::general.customer') }}</th>
                        <th class="px-4 py-3 text-left">{{ trans('pos::general.default_payment_method') }}</th>
                        <th class="px-4 py-3 text-left">{{ trans('pos::general.total') }}</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">{{ trans('general.date') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($orders as $order)
                        <tr>
                            <td class="px-4 py-3">
                                <a class="text-emerald-700 hover:underline" href="{{ route('pos.orders.show', $order->id) }}">{{ $order->order_number }}</a>
                                @if ($order->tab_name)
                                    <div class="text-xs text-gray-500">{{ $order->tab_name }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $order->contact_id ? $order->contact->name : trans('pos::general.walk_in_customer') }}</td>
                            <td class="px-4 py-3">{{ $order->payment_method_label }}</td>
                            <td class="px-4 py-3">@money($order->total, setting('default.currency', 'USD'), true)</td>
                            <td class="px-4 py-3">{{ $order->status_label }}</td>
                            <td class="px-4 py-3">{{ company_date($order->created_at) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">{{ trans('general.no_records') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $orders->links() }}
        </div>
    </section>
</x-layouts.admin>

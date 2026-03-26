<x-layouts.admin>
    <x-slot name="title">{{ trans('crm::general.reports') }}</x-slot>

    <x-slot name="buttons">
        <div class="flex gap-2">
            <x-link href="{{ route('crm.deals.index') }}" kind="secondary">{{ trans('crm::general.board') }}</x-link>
        </div>
    </x-slot>

    <x-slot name="content">
        <form method="GET" action="{{ route('crm.reports.deals') }}" class="mb-4 flex flex-wrap gap-3 rounded-xl bg-white p-4 shadow-sm">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">From</label>
                <input type="date" name="from" value="{{ $from }}" class="rounded-lg border-gray-300" />
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">To</label>
                <input type="date" name="to" value="{{ $to }}" class="rounded-lg border-gray-300" />
            </div>
            <div class="flex items-end">
                <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2 text-sm text-white">{{ trans('general.search') }}</button>
            </div>
        </form>

        <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-4">
            <div class="rounded-xl bg-white p-5 shadow-sm">
                <div class="text-sm text-gray-500">{{ trans('crm::general.pipeline_value') }}</div>
                <div class="mt-2 text-2xl font-semibold">{{ money($valueByStage->sum('open_value'), setting('default.currency', 'USD')) }}</div>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-sm">
                <div class="text-sm text-gray-500">{{ trans('crm::general.open_deals') }}</div>
                <div class="mt-2 text-2xl font-semibold">{{ $valueByStage->sum('open_count') }}</div>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-sm">
                <div class="text-sm text-gray-500">{{ trans('crm::general.won_deals') }}</div>
                <div class="mt-2 text-2xl font-semibold">{{ $wonCount }}</div>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-sm">
                <div class="text-sm text-gray-500">{{ trans('crm::general.conversion_rate') }}</div>
                <div class="mt-2 text-2xl font-semibold">{{ $closedCount > 0 ? number_format(($wonCount / $closedCount) * 100, 2) : '0.00' }}%</div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="rounded-xl bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold">{{ trans('crm::general.pipeline_value') }}</h2>
                <div class="space-y-3">
                    @foreach ($valueByStage as $stage)
                        <div class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="inline-block h-3 w-3 rounded-full" style="background-color: {{ $stage->color }}"></span>
                                <span>{{ $stage->name }}</span>
                            </div>
                            <div class="text-right">
                                <div class="font-medium">{{ money($stage->open_value ?: 0, setting('default.currency', 'USD')) }}</div>
                                <div class="text-xs text-gray-500">{{ $stage->open_count }} {{ trans('crm::general.deals') }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold">{{ trans('crm::general.won_deals') }} / {{ trans('crm::general.lost_deals') }}</h2>
                <div class="space-y-3">
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3">
                        <div class="font-medium text-emerald-800">{{ trans('crm::general.won_deals') }}</div>
                        <div>{{ optional($wonLostByPeriod->get('won'))->deal_count ?? 0 }} deals</div>
                        <div>{{ money(optional($wonLostByPeriod->get('won'))->total_value ?? 0, setting('default.currency', 'USD')) }}</div>
                    </div>
                    <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3">
                        <div class="font-medium text-rose-800">{{ trans('crm::general.lost_deals') }}</div>
                        <div>{{ optional($wonLostByPeriod->get('lost'))->deal_count ?? 0 }} deals</div>
                        <div>{{ money(optional($wonLostByPeriod->get('lost'))->total_value ?? 0, setting('default.currency', 'USD')) }}</div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="font-medium text-slate-800">{{ trans('crm::general.conversion_rate') }}</div>
                        <div>{{ $closedCount > 0 ? number_format(($wonCount / $closedCount) * 100, 2) : '0.00' }}%</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 rounded-xl bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">{{ trans('crm::general.growth_report') }}</h2>
            <div class="overflow-hidden rounded-lg border border-gray-200">
                <table class="w-full">
                    <thead>
                        <tr class="border-b bg-gray-50">
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Month</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('crm::general.deals') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('crm::general.pipeline_value') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('crm::general.won_deals') }}</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('crm::general.deal_value') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($growthReport as $row)
                            <tr class="border-b">
                                <td class="px-4 py-3 text-sm">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $row->month_key)->format('M Y') }}</td>
                                <td class="px-4 py-3 text-sm">{{ $row->created_count }}</td>
                                <td class="px-4 py-3 text-sm">{{ money($row->created_value, setting('default.currency', 'USD')) }}</td>
                                <td class="px-4 py-3 text-sm">{{ optional($wonGrowth->get($row->month_key))->won_count ?? 0 }}</td>
                                <td class="px-4 py-3 text-sm">{{ money(optional($wonGrowth->get($row->month_key))->won_value ?? 0, setting('default.currency', 'USD')) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">{{ trans('crm::general.deals') }}: 0</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-slot>
</x-layouts.admin>

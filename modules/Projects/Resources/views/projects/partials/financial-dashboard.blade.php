<div class="grid grid-cols-1 xl:grid-cols-4 gap-4">
    <div class="rounded-2xl bg-emerald-50 p-4">
        <div class="text-sm text-emerald-700">{{ trans('projects::general.revenue') }}</div>
        <div class="mt-1 text-2xl font-semibold text-emerald-900">{{ money($report['summary']['revenue'], setting('default.currency', 'USD')) }}</div>
    </div>
    <div class="rounded-2xl bg-rose-50 p-4">
        <div class="text-sm text-rose-700">{{ trans('projects::general.costs') }}</div>
        <div class="mt-1 text-2xl font-semibold text-rose-900">{{ money($report['summary']['costs'], setting('default.currency', 'USD')) }}</div>
    </div>
    <div class="rounded-2xl bg-blue-50 p-4">
        <div class="text-sm text-blue-700">{{ trans('projects::general.profit') }}</div>
        <div class="mt-1 text-2xl font-semibold text-blue-900">{{ money($report['summary']['profit'], setting('default.currency', 'USD')) }}</div>
    </div>
    <div class="rounded-2xl bg-amber-50 p-4">
        <div class="text-sm text-amber-700">{{ trans('projects::general.remaining_budget') }}</div>
        <div class="mt-1 text-2xl font-semibold text-amber-900">{{ money($report['budget']['remaining'], setting('default.currency', 'USD')) }}</div>
    </div>
</div>

@if ($report['budget']['over_budget'])
    <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
        {{ trans('projects::general.over_budget_alert') }}
    </div>
@endif

<div class="mt-6 grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="rounded-2xl bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-lg font-semibold text-slate-900">{{ trans('projects::general.budget_vs_actual') }}</h3>
            @if (!empty($showReportLink))
                <x-link href="{{ route('projects.projects.reports.pnl', $project->id) }}">
                    {{ trans('projects::general.view_report') }}
                </x-link>
            @endif
        </div>
        <div class="mt-4 space-y-4 text-sm">
            <div class="flex items-center justify-between">
                <span class="text-gray-500">{{ trans('projects::general.budget') }}</span>
                <span class="font-medium">{{ money($report['budget']['planned'], setting('default.currency', 'USD')) }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-500">{{ trans('projects::general.actual_costs') }}</span>
                <span class="font-medium">{{ money($report['budget']['actual'], setting('default.currency', 'USD')) }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-500">{{ trans('projects::general.variance') }}</span>
                <span class="font-medium {{ $report['budget']['variance'] < 0 ? 'text-rose-600' : 'text-emerald-700' }}">
                    {{ money($report['budget']['variance'], setting('default.currency', 'USD')) }}
                </span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-500">{{ trans('projects::general.variance_percentage') }}</span>
                <span class="font-medium">{{ $report['budget']['variance_percentage'] !== null ? number_format($report['budget']['variance_percentage'], 2) . '%' : '-' }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-500">{{ trans('projects::general.burn_rate') }}</span>
                <span class="font-medium">{{ money($report['budget']['burn_rate'], setting('default.currency', 'USD')) }}/{{ trans('projects::general.day') }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-500">{{ trans('projects::general.projected_completion_cost') }}</span>
                <span class="font-medium">{{ money($report['budget']['projected_completion_cost'], setting('default.currency', 'USD')) }}</span>
            </div>
        </div>
    </div>

    <div class="rounded-2xl bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-slate-900">{{ trans('projects::general.pnl_report') }}</h3>
        <div class="mt-4 space-y-4 text-sm">
            <div class="flex items-center justify-between">
                <span class="text-gray-500">{{ trans('projects::general.revenue') }}</span>
                <span class="font-medium">{{ money($report['summary']['revenue'], setting('default.currency', 'USD')) }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-500">{{ trans('projects::general.document_costs') }}</span>
                <span class="font-medium">{{ money($report['summary']['document_costs'], setting('default.currency', 'USD')) }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-500">{{ trans('projects::general.labor_cost') }}</span>
                <span class="font-medium">{{ money($report['summary']['labor_cost'], setting('default.currency', 'USD')) }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-500">{{ trans('projects::general.tracked_hours') }}</span>
                <span class="font-medium">{{ number_format($report['summary']['tracked_hours'], 2) }}h</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-500">{{ trans('projects::general.billable_hours') }}</span>
                <span class="font-medium">{{ number_format($report['summary']['billable_hours'], 2) }}h</span>
            </div>
            <div class="flex items-center justify-between border-t border-gray-100 pt-4">
                <span class="text-gray-500">{{ trans('projects::general.profit') }}</span>
                <span class="font-semibold {{ $report['summary']['profit'] < 0 ? 'text-rose-600' : 'text-emerald-700' }}">
                    {{ money($report['summary']['profit'], setting('default.currency', 'USD')) }}
                </span>
            </div>
        </div>
    </div>
</div>

<div class="mt-6 grid grid-cols-1 xl:grid-cols-2 gap-6">
    <div class="rounded-2xl bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-slate-900">{{ trans('projects::general.budget_burn_rate_chart') }}</h3>
        <div class="mt-6 flex h-64 items-end gap-3 overflow-x-auto">
            @foreach ($report['burn']['points'] as $point)
                @php
                    $actualHeight = max(6, (int) round(($point['actual'] / $report['burn']['max']) * 220));
                    $budgetHeight = $point['budget'] > 0 ? max(6, (int) round(($point['budget'] / $report['burn']['max']) * 220)) : 6;
                @endphp
                <div class="min-w-[64px] flex-1">
                    <div class="flex h-56 items-end justify-center gap-2">
                        <div class="w-5 rounded-t-full bg-slate-200" style="height: {{ $budgetHeight }}px" title="{{ trans('projects::general.budget') }}"></div>
                        <div class="w-5 rounded-t-full bg-blue-600" style="height: {{ $actualHeight }}px" title="{{ trans('projects::general.actual_costs') }}"></div>
                    </div>
                    <div class="mt-3 text-center text-xs text-gray-500">{{ $point['label'] }}</div>
                </div>
            @endforeach
        </div>
        <div class="mt-4 flex gap-4 text-xs text-gray-500">
            <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-blue-600"></span>{{ trans('projects::general.actual_costs') }}</span>
            <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-slate-300"></span>{{ trans('projects::general.budget') }}</span>
        </div>
    </div>

    <div class="rounded-2xl bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-slate-900">{{ trans('projects::general.cash_flow_statement') }}</h3>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-gray-500">
                        <th class="px-3 py-2">{{ trans('general.month') }}</th>
                        <th class="px-3 py-2">{{ trans('projects::general.inflows') }}</th>
                        <th class="px-3 py-2">{{ trans('projects::general.outflows') }}</th>
                        <th class="px-3 py-2">{{ trans('projects::general.net_cash_flow') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['cash_flow']['months'] as $month)
                        <tr class="border-b border-gray-100">
                            <td class="px-3 py-3 font-medium text-slate-900">{{ $month['label'] }}</td>
                            <td class="px-3 py-3">{{ money($month['inflows'], setting('default.currency', 'USD')) }}</td>
                            <td class="px-3 py-3">{{ money($month['outflows'], setting('default.currency', 'USD')) }}</td>
                            <td class="px-3 py-3 {{ $month['net'] < 0 ? 'text-rose-600' : 'text-emerald-700' }}">{{ money($month['net'], setting('default.currency', 'USD')) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-6 text-center text-gray-500">{{ trans('projects::general.empty_state') }}</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="font-semibold text-slate-900">
                        <td class="px-3 py-3">{{ trans('general.total') }}</td>
                        <td class="px-3 py-3">{{ money($report['cash_flow']['totals']['inflows'], setting('default.currency', 'USD')) }}</td>
                        <td class="px-3 py-3">{{ money($report['cash_flow']['totals']['outflows'], setting('default.currency', 'USD')) }}</td>
                        <td class="px-3 py-3">{{ money($report['cash_flow']['totals']['inflows'] - $report['cash_flow']['totals']['outflows'], setting('default.currency', 'USD')) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

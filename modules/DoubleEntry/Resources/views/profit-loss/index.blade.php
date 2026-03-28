@extends('layouts.admin')

@section('title', trans('double-entry::general.profit_loss'))

@section('content')
    @php
        $sections = [
            'income' => trans('double-entry::general.income'),
            'expenses' => trans('double-entry::general.expenses'),
        ];

        $exportUrl = route('double-entry.profit-loss.index', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'export' => 'csv',
        ]);

        $netProfitClass = $profitLoss['net_profit'] >= 0 ? 'text-green-700' : 'text-red-700';
    @endphp

    <div class="space-y-6">
        <div class="flex items-center justify-between gap-3">
            <h1 class="text-xl font-semibold text-gray-900">{{ trans('double-entry::general.profit_loss') }}</h1>

            <a href="{{ $exportUrl }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                {{ trans('double-entry::general.export_csv') }}
            </a>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('double-entry.profit-loss.index') }}" class="grid gap-4 md:grid-cols-3">
                <x-form.group.date name="start_date" label="{{ trans('double-entry::general.date_from') }}" :value="$startDate" />
                <x-form.group.date name="end_date" label="{{ trans('double-entry::general.date_to') }}" :value="$endDate" />

                <div class="flex items-end">
                    <button type="submit" class="inline-flex items-center rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                        {{ trans('double-entry::general.apply') }}
                    </button>
                </div>
            </form>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            @foreach ($sections as $key => $label)
                <div class="rounded-xl bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-5 py-4">
                        <h2 class="text-lg font-semibold text-gray-900">{{ $label }}</h2>
                    </div>

                    <x-table>
                        <x-table.tbody>
                            @forelse ($profitLoss[$key]['accounts'] as $row)
                                <x-table.tr>
                                    <x-table.td>{{ $row['label'] }}</x-table.td>
                                    <x-table.td class="text-right">{{ number_format($row['balance'], 4) }}</x-table.td>
                                </x-table.tr>
                            @empty
                                <x-table.tr>
                                    <x-table.td colspan="2" class="py-4 text-center text-sm text-gray-500">
                                        {{ trans('double-entry::general.no_accounts') }}
                                    </x-table.td>
                                </x-table.tr>
                            @endforelse

                            <x-table.tr>
                                <x-table.td class="bg-gray-50 font-semibold text-gray-900">{{ $label }} {{ trans('double-entry::general.total') }}</x-table.td>
                                <x-table.td class="bg-gray-50 text-right font-semibold text-gray-900">{{ number_format($profitLoss[$key]['total'], 4) }}</x-table.td>
                            </x-table.tr>
                        </x-table.tbody>
                    </x-table>
                </div>
            @endforeach
        </div>

        <div class="rounded-xl bg-white px-5 py-4 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-lg font-semibold text-gray-900">{{ trans('double-entry::general.net_profit_loss') }}</span>
                <span class="text-lg font-semibold {{ $netProfitClass }}">{{ number_format($profitLoss['net_profit'], 4) }}</span>
            </div>
        </div>
    </div>
@endsection

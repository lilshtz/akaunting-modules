@extends('layouts.admin')

@section('title', trans('double-entry::general.balance_sheet'))

@section('content')
    @php
        $sections = [
            'assets' => trans('double-entry::general.assets'),
            'liabilities' => trans('double-entry::general.liabilities'),
            'equity' => trans('double-entry::general.equity'),
        ];

        $exportUrl = route('double-entry.balance-sheet.index', [
            'as_of_date' => $asOfDate,
            'export' => 'csv',
        ]);
    @endphp

    <div class="space-y-6">
        <div class="flex items-center justify-between gap-3">
            <h1 class="text-xl font-semibold text-gray-900">{{ trans('double-entry::general.balance_sheet') }}</h1>

            <a href="{{ $exportUrl }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                {{ trans('double-entry::general.export_csv') }}
            </a>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('double-entry.balance-sheet.index') }}" class="grid gap-4 md:grid-cols-2">
                <x-form.group.date name="as_of_date" label="{{ trans('double-entry::general.as_of_date') }}" :value="$asOfDate" />

                <div class="flex items-end">
                    <button type="submit" class="inline-flex items-center rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                        {{ trans('double-entry::general.apply') }}
                    </button>
                </div>
            </form>
        </div>

        @if ($outOfBalance !== 0.0)
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                {{ trans('double-entry::general.out_of_balance') }}: {{ number_format($outOfBalance, 4) }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            @foreach ($sections as $key => $label)
                <div class="rounded-xl bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-5 py-4">
                        <h2 class="text-lg font-semibold text-gray-900">{{ $label }}</h2>
                    </div>

                    <x-table>
                        <x-table.tbody>
                            @forelse ($balanceSheet[$key]['accounts'] as $row)
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
                                <x-table.td class="bg-gray-50 text-right font-semibold text-gray-900">{{ number_format($balanceSheet[$key]['total'], 4) }}</x-table.td>
                            </x-table.tr>
                        </x-table.tbody>
                    </x-table>
                </div>
            @endforeach
        </div>

        <div class="rounded-xl bg-white shadow-sm">
            <x-table>
                <x-table.tbody>
                    <x-table.tr>
                        <x-table.td class="font-semibold text-gray-900">{{ trans('double-entry::general.total_assets') }}</x-table.td>
                        <x-table.td class="text-right font-semibold text-gray-900">{{ number_format($balanceSheet['total_assets'], 4) }}</x-table.td>
                    </x-table.tr>
                    <x-table.tr>
                        <x-table.td class="font-semibold text-gray-900">{{ trans('double-entry::general.total_liabilities_equity') }}</x-table.td>
                        <x-table.td class="text-right font-semibold text-gray-900">{{ number_format($balanceSheet['total_liabilities_equity'], 4) }}</x-table.td>
                    </x-table.tr>
                </x-table.tbody>
            </x-table>
        </div>
    </div>
@endsection

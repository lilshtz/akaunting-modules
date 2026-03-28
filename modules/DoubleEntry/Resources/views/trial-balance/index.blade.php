@extends('layouts.admin')

@section('title', trans('double-entry::general.trial_balance'))

@section('content')
    @php
        $typeLabels = [
            'asset' => trans('double-entry::general.assets'),
            'liability' => trans('double-entry::general.liabilities'),
            'equity' => trans('double-entry::general.equity'),
            'income' => trans('double-entry::general.income'),
            'expense' => trans('double-entry::general.expenses'),
        ];

        $exportUrl = route('double-entry.trial-balance.index', [
            'as_of_date' => $asOfDate,
            'export' => 'csv',
        ]);
    @endphp

    <div class="space-y-6">
        <div class="flex items-center justify-between gap-3">
            <h1 class="text-xl font-semibold text-gray-900">{{ trans('double-entry::general.trial_balance') }}</h1>

            <a href="{{ $exportUrl }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                {{ trans('double-entry::general.export_csv') }}
            </a>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('double-entry.trial-balance.index') }}" class="grid gap-4 md:grid-cols-2">
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

        <div class="rounded-xl bg-white shadow-sm">
            <x-table>
                <x-table.thead>
                    <x-table.tr>
                        <x-table.th>{{ trans('double-entry::general.account_code') }}</x-table.th>
                        <x-table.th>{{ trans('general.name') }}</x-table.th>
                        <x-table.th>{{ trans('double-entry::general.debit') }}</x-table.th>
                        <x-table.th>{{ trans('double-entry::general.credit') }}</x-table.th>
                    </x-table.tr>
                </x-table.thead>

                <x-table.tbody>
                    @foreach ($trialBalance['accounts'] as $type => $rows)
                        <x-table.tr>
                            <x-table.td colspan="4" class="bg-gray-50 text-sm font-semibold uppercase tracking-wide text-gray-600">
                                {{ $typeLabels[$type] }}
                            </x-table.td>
                        </x-table.tr>

                        @forelse ($rows as $row)
                            <x-table.tr>
                                <x-table.td>{{ $row['account']->code }}</x-table.td>
                                <x-table.td>{{ $row['account']->name }}</x-table.td>
                                <x-table.td>{{ number_format($row['debit'], 4) }}</x-table.td>
                                <x-table.td>{{ number_format($row['credit'], 4) }}</x-table.td>
                            </x-table.tr>
                        @empty
                            <x-table.tr>
                                <x-table.td colspan="4" class="py-4 text-center text-sm text-gray-500">
                                    {{ trans('double-entry::general.no_accounts') }}
                                </x-table.td>
                            </x-table.tr>
                        @endforelse
                    @endforeach

                    <x-table.tr>
                        <x-table.td colspan="2" class="bg-gray-50 font-semibold text-gray-900">{{ trans('double-entry::general.total') }}</x-table.td>
                        <x-table.td class="bg-gray-50 font-semibold text-gray-900">{{ number_format($trialBalance['grand_debit'], 4) }}</x-table.td>
                        <x-table.td class="bg-gray-50 font-semibold text-gray-900">{{ number_format($trialBalance['grand_credit'], 4) }}</x-table.td>
                    </x-table.tr>
                </x-table.tbody>
            </x-table>
        </div>
    </div>
@endsection

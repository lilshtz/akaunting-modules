@extends('layouts.admin')

@section('title', trans('double-entry::general.general_ledger'))

@section('content')
    @php
        $accountOptions = ['' => trans('double-entry::general.all_accounts')] + $accounts->mapWithKeys(fn ($account) => [
            $account->id => $account->code . ' - ' . $account->name,
        ])->all();

        $exportUrl = route('double-entry.general-ledger.index', array_filter([
            'account_id' => $selectedAccountId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'export' => 'csv',
        ], fn ($value) => $value !== null && $value !== ''));
    @endphp

    <div class="space-y-6">
        <div class="flex items-center justify-between gap-3">
            <h1 class="text-xl font-semibold text-gray-900">{{ trans('double-entry::general.general_ledger') }}</h1>

            <a href="{{ $exportUrl }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                {{ trans('double-entry::general.export_csv') }}
            </a>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('double-entry.general-ledger.index') }}" class="grid gap-4 md:grid-cols-4">
                <x-form.group.select name="account_id" label="{{ trans('double-entry::general.account') }}" :options="$accountOptions" :selected="(string) $selectedAccountId" />
                <x-form.group.date name="start_date" label="{{ trans('double-entry::general.date_from') }}" :value="$startDate" not-required />
                <x-form.group.date name="end_date" label="{{ trans('double-entry::general.date_to') }}" :value="$endDate" not-required />

                <div class="flex items-end">
                    <button type="submit" class="inline-flex items-center rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                        {{ trans('double-entry::general.apply') }}
                    </button>
                </div>
            </form>
        </div>

        <div class="rounded-xl bg-white shadow-sm">
            <x-table>
                <x-table.thead>
                    <x-table.tr>
                        <x-table.th>{{ trans('double-entry::general.date') }}</x-table.th>
                        <x-table.th>{{ trans('double-entry::general.reference') }}</x-table.th>
                        <x-table.th>{{ trans('double-entry::general.description') }}</x-table.th>
                        <x-table.th>{{ trans('double-entry::general.debit') }}</x-table.th>
                        <x-table.th>{{ trans('double-entry::general.credit') }}</x-table.th>
                        <x-table.th>{{ trans('double-entry::general.running_balance') }}</x-table.th>
                    </x-table.tr>
                </x-table.thead>

                <x-table.tbody>
                    @forelse ($groupedEntries as $group)
                        <x-table.tr>
                            <x-table.td colspan="6" class="bg-gray-50 text-sm font-semibold text-gray-700">
                                {{ $group['account']['code'] }} - {{ $group['account']['name'] }}
                                <span class="ml-3 text-xs font-medium uppercase tracking-wide text-gray-500">{{ trans('double-entry::general.' . $group['account']['type']) }}</span>
                            </x-table.td>
                        </x-table.tr>

                        <x-table.tr>
                            <x-table.td colspan="5" class="text-sm text-gray-500">{{ trans('double-entry::general.opening_balance') }}</x-table.td>
                            <x-table.td class="font-medium">{{ number_format($group['opening_balance'], 4) }}</x-table.td>
                        </x-table.tr>

                        @forelse ($group['rows'] as $row)
                            <x-table.tr>
                                <x-table.td>{{ $row['date'] }}</x-table.td>
                                <x-table.td>{{ $row['reference'] ?: '-' }}</x-table.td>
                                <x-table.td>{{ $row['description'] ?: '-' }}</x-table.td>
                                <x-table.td>{{ number_format($row['debit'], 4) }}</x-table.td>
                                <x-table.td>{{ number_format($row['credit'], 4) }}</x-table.td>
                                <x-table.td>{{ number_format($row['running_balance'], 4) }}</x-table.td>
                            </x-table.tr>
                        @empty
                            <x-table.tr>
                                <x-table.td colspan="6" class="py-4 text-center text-sm text-gray-500">
                                    {{ trans('double-entry::general.no_journals') }}
                                </x-table.td>
                            </x-table.tr>
                        @endforelse

                        <x-table.tr>
                            <x-table.td colspan="3" class="bg-gray-50 font-semibold text-gray-700">{{ trans('double-entry::general.subtotal') }}</x-table.td>
                            <x-table.td class="bg-gray-50 font-semibold">{{ number_format($group['subtotal_debit'], 4) }}</x-table.td>
                            <x-table.td class="bg-gray-50 font-semibold">{{ number_format($group['subtotal_credit'], 4) }}</x-table.td>
                            <x-table.td class="bg-gray-50 font-semibold">{{ number_format($group['closing_balance'], 4) }}</x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.tr>
                            <x-table.td colspan="6" class="py-6 text-center text-sm text-gray-500">
                                {{ trans('double-entry::general.no_journals') }}
                            </x-table.td>
                        </x-table.tr>
                    @endforelse
                </x-table.tbody>
            </x-table>
        </div>
    </div>
@endsection

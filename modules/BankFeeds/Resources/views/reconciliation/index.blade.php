@extends('layouts.admin')

@section('title', trans('bank-feeds::general.reconciliation'))

@section('content')
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ trans('bank-feeds::general.reconciliation') }}</h1>
                <p class="text-sm text-gray-500">{{ trans('bank-feeds::general.help.reconciliation') }}</p>
            </div>

            <a href="{{ route('bank-feeds.reconciliation.create') }}" class="inline-flex rounded-lg bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-900">
                {{ trans('bank-feeds::general.new_reconciliation') }}
            </a>
        </div>

        <div class="rounded-xl bg-white shadow-sm">
            <x-table>
                <x-table.thead>
                    <x-table.tr>
                        <x-table.th>{{ trans('bank-feeds::general.bank_account') }}</x-table.th>
                        <x-table.th>{{ trans('bank-feeds::general.period') }}</x-table.th>
                        <x-table.th>{{ trans('bank-feeds::general.opening_balance') }}</x-table.th>
                        <x-table.th>{{ trans('bank-feeds::general.closing_balance') }}</x-table.th>
                        <x-table.th>{{ trans('bank-feeds::general.status') }}</x-table.th>
                        <x-table.th>{{ trans('general.actions') }}</x-table.th>
                    </x-table.tr>
                </x-table.thead>
                <x-table.tbody>
                    @forelse ($reconciliations as $reconciliation)
                        <x-table.tr>
                            <x-table.td>{{ $reconciliation->bankAccount ? trim($reconciliation->bankAccount->code . ' - ' . $reconciliation->bankAccount->name) : '—' }}</x-table.td>
                            <x-table.td>{{ $reconciliation->period_start?->format('Y-m-d') }} to {{ $reconciliation->period_end?->format('Y-m-d') }}</x-table.td>
                            <x-table.td>{{ number_format((float) $reconciliation->opening_balance, 4) }}</x-table.td>
                            <x-table.td>{{ number_format((float) $reconciliation->closing_balance, 4) }}</x-table.td>
                            <x-table.td>{{ trans('bank-feeds::general.statuses.' . $reconciliation->status) }}</x-table.td>
                            <x-table.td>
                                <a href="{{ route('bank-feeds.reconciliation.show', $reconciliation->id) }}" class="text-sm font-medium text-gray-700 hover:text-gray-900">
                                    {{ trans('general.view') }}
                                </a>
                            </x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.tr>
                            <x-table.td colspan="6" class="py-6 text-center text-sm text-gray-500">
                                {{ trans('bank-feeds::general.transactions_empty') }}
                            </x-table.td>
                        </x-table.tr>
                    @endforelse
                </x-table.tbody>
            </x-table>
        </div>

        {{ $reconciliations->links() }}
    </div>
@endsection

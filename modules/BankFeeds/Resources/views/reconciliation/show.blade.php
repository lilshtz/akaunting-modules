@extends('layouts.admin')

@section('title', trans('bank-feeds::general.reconciliation'))

@section('content')
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ $reconciliation->bankAccount ? trim($reconciliation->bankAccount->code . ' - ' . $reconciliation->bankAccount->name) : trans('bank-feeds::general.reconciliation') }}</h1>
                <p class="text-sm text-gray-500">{{ $reconciliation->period_start?->format('Y-m-d') }} to {{ $reconciliation->period_end?->format('Y-m-d') }}</p>
            </div>

            @if ($canComplete && $reconciliation->status !== 'completed')
                <form method="POST" action="{{ route('bank-feeds.reconciliation.complete', $reconciliation->id) }}">
                    @csrf
                    <button type="submit" class="inline-flex rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                        {{ trans('bank-feeds::general.mark_reconciled') }}
                    </button>
                </form>
            @endif
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-xl bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-gray-500">{{ trans('bank-feeds::general.opening_balance') }}</div>
                <div class="mt-2 text-lg font-semibold text-gray-900">{{ number_format((float) $reconciliation->opening_balance, 4) }}</div>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-gray-500">{{ trans('bank-feeds::general.closing_balance') }}</div>
                <div class="mt-2 text-lg font-semibold text-gray-900">{{ number_format((float) $reconciliation->closing_balance, 4) }}</div>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-gray-500">{{ trans('bank-feeds::general.computed_balance') }}</div>
                <div class="mt-2 text-lg font-semibold text-gray-900">{{ number_format($computedBalance, 4) }}</div>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-gray-500">{{ trans('bank-feeds::general.difference') }}</div>
                <div class="mt-2 text-lg font-semibold {{ $canComplete ? 'text-green-600' : 'text-red-600' }}">{{ number_format($difference, 4) }}</div>
            </div>
        </div>

        <div class="rounded-xl bg-white shadow-sm">
            <x-table>
                <x-table.thead>
                    <x-table.tr>
                        <x-table.th>{{ trans('bank-feeds::general.date') }}</x-table.th>
                        <x-table.th>{{ trans('bank-feeds::general.description') }}</x-table.th>
                        <x-table.th>{{ trans('bank-feeds::general.deposits') }}</x-table.th>
                        <x-table.th>{{ trans('bank-feeds::general.withdrawals') }}</x-table.th>
                        <x-table.th>{{ trans('bank-feeds::general.journal_reference') }}</x-table.th>
                    </x-table.tr>
                </x-table.thead>
                <x-table.tbody>
                    @forelse ($transactions as $transaction)
                        <x-table.tr>
                            <x-table.td>{{ $transaction->date?->format('Y-m-d') }}</x-table.td>
                            <x-table.td>{{ $transaction->description }}</x-table.td>
                            <x-table.td>{{ $transaction->type === 'deposit' ? number_format((float) $transaction->amount, 4) : '—' }}</x-table.td>
                            <x-table.td>{{ $transaction->type === 'withdrawal' ? number_format(abs((float) $transaction->amount), 4) : '—' }}</x-table.td>
                            <x-table.td>{{ $transaction->matchedJournal?->reference ?? '—' }}</x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.tr>
                            <x-table.td colspan="5" class="py-6 text-center text-sm text-gray-500">
                                {{ trans('bank-feeds::general.transactions_empty') }}
                            </x-table.td>
                        </x-table.tr>
                    @endforelse
                </x-table.tbody>
            </x-table>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm">
            <div class="grid gap-4 md:grid-cols-4">
                <div>
                    <div class="text-sm font-medium text-gray-500">{{ trans('bank-feeds::general.opening_balance') }}</div>
                    <div class="mt-1 text-sm text-gray-900">{{ number_format((float) $reconciliation->opening_balance, 4) }}</div>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-500">{{ trans('bank-feeds::general.deposits') }}</div>
                    <div class="mt-1 text-sm text-gray-900">{{ number_format($deposits, 4) }}</div>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-500">{{ trans('bank-feeds::general.withdrawals') }}</div>
                    <div class="mt-1 text-sm text-gray-900">{{ number_format($withdrawals, 4) }}</div>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-500">{{ trans('bank-feeds::general.computed_balance') }}</div>
                    <div class="mt-1 text-sm text-gray-900">{{ number_format($computedBalance, 4) }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection

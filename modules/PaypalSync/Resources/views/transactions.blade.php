<x-layouts.admin>
    <x-slot name="title">{{ trans('paypal-sync::general.transactions') }}</x-slot>

    <x-slot name="favorite"
        title="{{ trans('paypal-sync::general.transactions') }}"
        icon="account_balance_wallet"
        route="paypal-sync.transactions.index"
    ></x-slot>

    <x-slot name="buttons">
        <x-form id="paypal-sync-form" method="POST" route="paypal-sync.transactions.sync">
            <x-button
                type="submit"
                class="relative flex items-center justify-center bg-green hover:bg-green-700 text-white px-3 py-1.5 text-sm rounded-lg"
                override="class"
            >
                <span class="material-icons-outlined text-base mr-1">sync</span>
                {{ trans('paypal-sync::general.sync_now') }}
            </x-button>
        </x-form>
    </x-slot>

    <x-slot name="content">
        <x-show.container>
            <x-table>
                <x-table.thead>
                    <x-table.tr class="flex items-center px-1">
                        <x-table.th class="w-2/12">
                            {{ trans('general.date') }}
                        </x-table.th>

                        <x-table.th class="w-2/12">
                            {{ trans('paypal-sync::general.transaction_id') }}
                        </x-table.th>

                        <x-table.th class="w-1/12">
                            {{ trans('general.amount') }}
                        </x-table.th>

                        <x-table.th class="w-1/12">
                            {{ trans('currencies.code') }}
                        </x-table.th>

                        <x-table.th class="w-2/12">
                            {{ trans('paypal-sync::general.payer_email') }}
                        </x-table.th>

                        <x-table.th class="w-1/12">
                            {{ trans('general.status') }}
                        </x-table.th>

                        <x-table.th class="w-2/12">
                            {{ trans('paypal-sync::general.matched') }}
                        </x-table.th>

                        <x-table.th class="w-1/12">
                            {{ trans('general.actions') }}
                        </x-table.th>
                    </x-table.tr>
                </x-table.thead>

                <x-table.tbody>
                    @forelse($transactions as $transaction)
                        <x-table.tr>
                            <x-table.td class="w-2/12">
                                {{ $transaction->date->format('Y-m-d') }}
                            </x-table.td>

                            <x-table.td class="w-2/12">
                                <span class="text-xs font-mono">{{ Str::limit($transaction->paypal_transaction_id, 20) }}</span>
                            </x-table.td>

                            <x-table.td class="w-1/12">
                                <span class="{{ $transaction->amount >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ number_format($transaction->amount, 2) }}
                                </span>
                            </x-table.td>

                            <x-table.td class="w-1/12">
                                {{ $transaction->currency }}
                            </x-table.td>

                            <x-table.td class="w-2/12">
                                {{ $transaction->payer_email ?? '-' }}
                            </x-table.td>

                            <x-table.td class="w-1/12">
                                @php
                                    $statusColors = [
                                        'completed' => 'bg-green-100 text-green-800',
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'refunded' => 'bg-blue-100 text-blue-800',
                                        'reversed' => 'bg-orange-100 text-orange-800',
                                        'denied' => 'bg-red-100 text-red-800',
                                    ];
                                    $statusClass = $statusColors[$transaction->status] ?? 'bg-gray-100 text-gray-800';
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                                    {{ trans('paypal-sync::general.status.' . $transaction->status) }}
                                </span>
                            </x-table.td>

                            <x-table.td class="w-2/12">
                                @if($transaction->bank_transaction_id)
                                    <span class="inline-flex items-center text-green-600">
                                        <span class="material-icons-outlined text-sm mr-1">check_circle</span>
                                        {{ trans('paypal-sync::general.matched') }}
                                    </span>
                                @else
                                    <span class="text-gray-400">
                                        {{ trans('paypal-sync::general.unmatched') }}
                                    </span>
                                @endif
                            </x-table.td>

                            <x-table.td class="w-1/12">
                                @if(!$transaction->bank_transaction_id && $transaction->status === 'completed')
                                    <x-button
                                        type="button"
                                        class="relative bg-white hover:bg-gray-100 border py-0.5 px-1 cursor-pointer index-actions"
                                        override="class"
                                        title="{{ trans('paypal-sync::general.match') }}"
                                    >
                                        <span class="material-icons-outlined text-purple text-lg">link</span>
                                    </x-button>
                                @endif
                            </x-table.td>
                        </x-table.tr>
                    @empty
                        <x-table.tr>
                            <x-table.td colspan="8">
                                <div class="flex justify-center items-center py-8">
                                    <span class="text-gray-400">{{ trans('general.no_records') }}</span>
                                </div>
                            </x-table.td>
                        </x-table.tr>
                    @endforelse
                </x-table.tbody>
            </x-table>

            @if($transactions->hasPages())
                <div class="mt-4 px-4">
                    {{ $transactions->links() }}
                </div>
            @endif
        </x-show.container>
    </x-slot>
</x-layouts.admin>

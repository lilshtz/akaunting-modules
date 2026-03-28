@extends('layouts.admin')

@section('title', trans('bank-feeds::general.transaction_matching'))

@section('content')
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ trans('bank-feeds::general.transaction_matching') }}</h1>
                <p class="text-sm text-gray-500">{{ trans('bank-feeds::general.help.matching') }}</p>
            </div>

            <form method="POST" action="{{ route('bank-feeds.matching.auto-match') }}">
                @csrf
                <button type="submit" class="inline-flex items-center rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                    {{ trans('bank-feeds::general.auto_match_all') }}
                </button>
            </form>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-xl bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-gray-500">{{ trans('bank-feeds::general.unmatched') }}</div>
                <div class="mt-2 text-2xl font-semibold text-gray-900">{{ $summary['unmatched'] }}</div>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-gray-500">{{ trans('bank-feeds::general.high_confidence') }}</div>
                <div class="mt-2 text-2xl font-semibold text-gray-900">{{ $summary['high_confidence'] }}</div>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-gray-500">{{ trans('bank-feeds::general.statuses.ignored') }}</div>
                <div class="mt-2 text-2xl font-semibold text-gray-900">{{ $summary['ignored'] }}</div>
            </div>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('bank-feeds.matching.index') }}" class="grid gap-4 md:grid-cols-3">
                <x-form.group.select
                    name="status"
                    label="{{ trans('bank-feeds::general.status') }}"
                    :options="['unmatched' => trans('bank-feeds::general.unmatched'), 'matched' => trans('bank-feeds::general.statuses.matched'), 'ignored' => trans('bank-feeds::general.statuses.ignored')]"
                    :selected="$status"
                />
                <x-form.group.text
                    name="confidence_threshold"
                    label="{{ trans('bank-feeds::general.confidence_threshold') }}"
                    :value="$confidenceThreshold"
                    type="number"
                    min="0"
                    max="100"
                    not-required
                />
                <div class="flex items-end justify-end gap-3">
                    <a href="{{ route('bank-feeds.matching.index') }}" class="inline-flex rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        {{ trans('general.reset') }}
                    </a>
                    <button type="submit" class="inline-flex rounded-lg bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-900">
                        {{ trans('general.filter') }}
                    </button>
                </div>
            </form>
        </div>

        <form id="bulk-ignore-form" method="POST" action="{{ route('bank-feeds.matching.bulk-ignore') }}">
            @csrf
        </form>

        <div class="space-y-4">
            <div class="flex justify-end">
                <button type="submit" form="bulk-ignore-form" class="inline-flex rounded-lg bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-900">
                    {{ trans('bank-feeds::general.ignore_selected') }}
                </button>
            </div>

            <div class="rounded-xl bg-white shadow-sm">
                <x-table>
                    <x-table.thead>
                        <x-table.tr>
                            <x-table.th><input type="checkbox" onclick="document.querySelectorAll('.transaction-checkbox').forEach((checkbox) => checkbox.checked = this.checked)"></x-table.th>
                            <x-table.th>{{ trans('bank-feeds::general.date') }}</x-table.th>
                            <x-table.th>{{ trans('bank-feeds::general.description') }}</x-table.th>
                            <x-table.th>{{ trans('bank-feeds::general.amount') }}</x-table.th>
                            <x-table.th>{{ trans('bank-feeds::general.type') }}</x-table.th>
                            <x-table.th>{{ trans('bank-feeds::general.top_match') }}</x-table.th>
                            <x-table.th>{{ trans('bank-feeds::general.status') }}</x-table.th>
                            <x-table.th>{{ trans('general.actions') }}</x-table.th>
                        </x-table.tr>
                    </x-table.thead>
                    <x-table.tbody>
                        @forelse ($transactions as $transaction)
                            @php($topMatch = $suggestions[$transaction->id][0] ?? null)
                            <x-table.tr>
                                <x-table.td><input class="transaction-checkbox" type="checkbox" name="transaction_ids[]" value="{{ $transaction->id }}" form="bulk-ignore-form"></x-table.td>
                                <x-table.td>{{ $transaction->date?->format('Y-m-d') }}</x-table.td>
                                <x-table.td>{{ $transaction->description }}</x-table.td>
                                <x-table.td>{{ number_format((float) $transaction->amount, 4) }}</x-table.td>
                                <x-table.td>{{ trans('bank-feeds::general.types.' . $transaction->type) }}</x-table.td>
                                <x-table.td>
                                    @if ($transaction->status === 'matched' && $transaction->matchedJournal)
                                        <div class="text-sm font-medium text-gray-900">{{ $transaction->matchedJournal->reference }}</div>
                                    @elseif ($topMatch)
                                        <div class="text-sm font-medium text-gray-900">{{ $topMatch['journal']->reference }}</div>
                                        <div class="text-xs {{ $topMatch['score'] > 80 ? 'text-green-600' : ($topMatch['score'] >= 50 ? 'text-amber-600' : 'text-red-600') }}">
                                            {{ trans('bank-feeds::general.match_score') }}: {{ $topMatch['score'] }}
                                        </div>
                                    @else
                                        <span class="text-sm text-gray-500">—</span>
                                    @endif
                                </x-table.td>
                                <x-table.td>{{ trans('bank-feeds::general.statuses.' . $transaction->status) }}</x-table.td>
                                <x-table.td>
                                    <div class="flex flex-wrap gap-3 text-sm">
                                        <a href="{{ route('bank-feeds.matching.show', $transaction->id) }}" class="font-medium text-gray-700 hover:text-gray-900">
                                            {{ trans('bank-feeds::general.view_matches') }}
                                        </a>

                                        @if ($topMatch && in_array($transaction->status, ['pending', 'categorized'], true))
                                            <form method="POST" action="{{ route('bank-feeds.matching.accept', $transaction->id) }}">
                                                @csrf
                                                <input type="hidden" name="journal_id" value="{{ $topMatch['journal_id'] }}">
                                                <button type="submit" class="font-medium text-green-600 hover:text-green-800">
                                                    {{ trans('bank-feeds::general.accept_top') }}
                                                </button>
                                            </form>
                                        @endif

                                        @if ($transaction->status !== 'ignored')
                                            <form method="POST" action="{{ route('bank-feeds.matching.bulk-ignore') }}">
                                                @csrf
                                                <input type="hidden" name="transaction_ids[]" value="{{ $transaction->id }}">
                                                <button type="submit" class="font-medium text-red-600 hover:text-red-800">
                                                    {{ trans('bank-feeds::general.ignore') }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </x-table.td>
                            </x-table.tr>
                        @empty
                            <x-table.tr>
                                <x-table.td colspan="8" class="py-6 text-center text-sm text-gray-500">
                                    {{ trans('bank-feeds::general.transactions_empty') }}
                                </x-table.td>
                            </x-table.tr>
                        @endforelse
                    </x-table.tbody>
                </x-table>
            </div>
        </div>

        {{ $transactions->links() }}
    </div>
@endsection

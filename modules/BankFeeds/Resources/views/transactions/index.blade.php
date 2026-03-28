@extends('layouts.admin')

@section('title', trans('bank-feeds::general.transaction_review'))

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">{{ trans('bank-feeds::general.transaction_review') }}</h1>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('bank-feeds.transactions.index') }}" class="grid gap-4 md:grid-cols-4">
                <x-form.group.select
                    name="status"
                    label="{{ trans('bank-feeds::general.status') }}"
                    :options="['' => '', 'pending' => trans('bank-feeds::general.statuses.pending'), 'categorized' => trans('bank-feeds::general.statuses.categorized'), 'matched' => trans('bank-feeds::general.statuses.matched'), 'ignored' => trans('bank-feeds::general.statuses.ignored')]"
                    :value="request('status')"
                    not-required
                />
                <x-form.group.select
                    name="import"
                    label="{{ trans('bank-feeds::general.import') }}"
                    :options="$imports->pluck('original_filename', 'id')->prepend('', '')->all()"
                    :value="request('import')"
                    not-required
                />
                <x-form.group.date name="date_from" label="{{ trans('bank-feeds::general.date_from') }}" :value="request('date_from')" not-required />
                <x-form.group.date name="date_to" label="{{ trans('bank-feeds::general.date_to') }}" :value="request('date_to')" not-required />

                <div class="md:col-span-4 flex items-center justify-end gap-3">
                    <a href="{{ route('bank-feeds.transactions.index') }}" class="inline-flex rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        {{ trans('general.reset') }}
                    </a>
                    <button type="submit" class="inline-flex rounded-lg bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-900">
                        {{ trans('general.filter') }}
                    </button>
                </div>
            </form>
        </div>

        <form method="POST" action="{{ route('bank-feeds.transactions.bulk-ignore') }}" class="space-y-4">
            @csrf

            <div class="flex justify-end">
                <button type="submit" class="inline-flex rounded-lg bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-900">
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
                            <x-table.th>{{ trans('bank-feeds::general.category') }}</x-table.th>
                            <x-table.th>{{ trans('bank-feeds::general.status') }}</x-table.th>
                            <x-table.th>{{ trans('general.actions') }}</x-table.th>
                        </x-table.tr>
                    </x-table.thead>
                    <x-table.tbody>
                        @forelse ($transactions as $transaction)
                            <x-table.tr>
                                <x-table.td><input class="transaction-checkbox" type="checkbox" name="transaction_ids[]" value="{{ $transaction->id }}"></x-table.td>
                                <x-table.td>{{ $transaction->date?->format('Y-m-d') }}</x-table.td>
                                <x-table.td>
                                    {{ $transaction->description }}
                                    @if ($transaction->is_duplicate)
                                        <div class="mt-1 text-xs font-medium text-amber-600">{{ trans('bank-feeds::general.duplicate') }}</div>
                                    @endif
                                </x-table.td>
                                <x-table.td>{{ number_format((float) $transaction->amount, 4) }}</x-table.td>
                                <x-table.td>{{ trans('bank-feeds::general.types.' . $transaction->type) }}</x-table.td>
                                <x-table.td>{{ $transaction->category?->name ?? '—' }}</x-table.td>
                                <x-table.td>{{ trans('bank-feeds::general.statuses.' . $transaction->status) }}</x-table.td>
                                <x-table.td>
                                    @if ($transaction->status !== 'ignored')
                                        <form method="POST" action="{{ route('bank-feeds.transactions.ignore', $transaction->id) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800">{{ trans('bank-feeds::general.ignore') }}</button>
                                        </form>
                                    @endif
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
        </form>

        {{ $transactions->links() }}
    </div>
@endsection

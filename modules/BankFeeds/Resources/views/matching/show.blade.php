@extends('layouts.admin')

@section('title', trans('bank-feeds::general.transaction_matching'))

@section('content')
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ trans('bank-feeds::general.transaction_matching') }}</h1>
                <p class="text-sm text-gray-500">{{ $transaction->description }}</p>
            </div>

            <a href="{{ route('bank-feeds.matching.index') }}" class="inline-flex rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                {{ trans('double-entry::general.back_to_list') }}
            </a>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-xl bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">{{ trans('bank-feeds::general.transactions') }}</h2>
                <dl class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ trans('bank-feeds::general.date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $transaction->date?->format('Y-m-d') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ trans('bank-feeds::general.amount') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ number_format((float) $transaction->amount, 4) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ trans('bank-feeds::general.type') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ trans('bank-feeds::general.types.' . $transaction->type) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ trans('bank-feeds::general.bank_account') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $transaction->bankAccount ? trim($transaction->bankAccount->code . ' - ' . $transaction->bankAccount->name) : '—' }}</dd>
                    </div>
                    <div class="md:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">{{ trans('bank-feeds::general.description') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $transaction->description }}</dd>
                    </div>
                    <div class="md:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">{{ trans('bank-feeds::general.raw_data') }}</dt>
                        <dd class="mt-1 overflow-x-auto rounded-lg bg-gray-50 p-3 text-xs text-gray-700">{{ json_encode($transaction->raw_data_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-xl bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">{{ trans('bank-feeds::general.view_matches') }}</h2>
                <div class="mt-4 space-y-4">
                    @forelse ($matches as $match)
                        <div class="rounded-xl border border-gray-200 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-sm font-semibold text-gray-900">{{ $match['journal']->reference }}</div>
                                    <div class="text-sm text-gray-500">{{ $match['journal']->date?->format('Y-m-d') }}</div>
                                </div>
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium {{ $match['score'] > 80 ? 'bg-green-100 text-green-700' : ($match['score'] >= 50 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
                                    {{ trans('bank-feeds::general.confidence') }}: {{ $match['score'] }}
                                </span>
                            </div>

                            <div class="mt-3 text-sm text-gray-700">
                                <div>{{ $match['journal']->description ?: '—' }}</div>
                                <div class="mt-1">{{ trans('bank-feeds::general.amount') }}: {{ number_format((float) (abs((float) $match['line']->debit - (float) $match['line']->credit)), 4) }}</div>
                            </div>

                            <form method="POST" action="{{ route('bank-feeds.matching.accept', $transaction->id) }}" class="mt-4">
                                @csrf
                                <input type="hidden" name="journal_id" value="{{ $match['journal_id'] }}">
                                <button type="submit" class="inline-flex rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                                    {{ trans('bank-feeds::general.accept_match') }}
                                </button>
                            </form>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-gray-300 p-6 text-sm text-gray-500">
                            {{ trans('bank-feeds::general.transactions_empty') }}
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <x-form.container>
            <x-form id="create-bank-feed-journal" method="POST" :route="['bank-feeds.matching.create-journal', $transaction->id]">
                <x-form.section>
                    <x-slot name="head">
                        <x-form.section.head
                            title="{{ trans('bank-feeds::general.create_new_journal') }}"
                            description="{{ trans('bank-feeds::general.offset_account') }}"
                        />
                    </x-slot>

                    <x-form.group.select
                        name="account_id"
                        label="{{ trans('bank-feeds::general.offset_account') }}"
                        :options="$accountOptions"
                        :selected="old('account_id', $transaction->category_id)"
                    />
                </x-form.section>

                <x-form.section>
                    <x-slot name="foot">
                        <div class="flex flex-wrap items-center justify-end gap-3">
                            <button type="submit" class="inline-flex rounded-lg bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-900">
                                {{ trans('bank-feeds::general.create_journal') }}
                            </button>
                        </div>
                    </x-slot>
                </x-form.section>
            </x-form>
        </x-form.container>

        <div class="flex flex-wrap gap-3">
            <form method="POST" action="{{ route('bank-feeds.matching.reject', $transaction->id) }}">
                @csrf
                <button type="submit" class="inline-flex rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    {{ trans('bank-feeds::general.reject_match') }}
                </button>
            </form>

            <form method="POST" action="{{ route('bank-feeds.matching.bulk-ignore') }}">
                @csrf
                <input type="hidden" name="transaction_ids[]" value="{{ $transaction->id }}">
                <button type="submit" class="inline-flex rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">
                    {{ trans('bank-feeds::general.ignore_transaction') }}
                </button>
            </form>
        </div>
    </div>
@endsection

@extends('layouts.admin')

@section('title', trans('double-entry::general.view_journal'))

@section('content')
    @php
        $statusClass = $journal->status === 'posted'
            ? 'bg-green-100 text-green-700'
            : 'bg-yellow-100 text-yellow-700';
    @endphp

    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ $journal->reference }}</h1>
                <p class="text-sm text-gray-500">{{ $journal->date?->format('Y-m-d') }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('double-entry.journals.index') }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    {{ trans('double-entry::general.back_to_list') }}
                </a>

                @if ($journal->isEditable())
                    <a href="{{ route('double-entry.journals.edit', $journal->id) }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        {{ trans('general.edit') }}
                    </a>

                    <form action="{{ route('double-entry.journals.post', $journal->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="inline-flex items-center rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                            {{ trans('double-entry::general.post') }}
                        </button>
                    </form>
                @else
                    <form action="{{ route('double-entry.journals.unpost', $journal->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            {{ trans('double-entry::general.unpost') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-xl bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-gray-500">{{ trans('double-entry::general.status') }}</div>
                <div class="mt-2">
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium {{ $statusClass }}">
                        {{ trans('double-entry::general.' . $journal->status) }}
                    </span>
                </div>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-gray-500">{{ trans('double-entry::general.basis') }}</div>
                <div class="mt-2 text-sm text-gray-900">{{ trans('double-entry::general.' . $journal->basis) }}</div>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-gray-500">{{ trans('double-entry::general.total_amount') }}</div>
                <div class="mt-2 text-sm text-gray-900">{{ number_format((float) $journal->total, 4) }}</div>
            </div>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm">
            <dl class="grid gap-4 md:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ trans('general.description') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $journal->description ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ trans('double-entry::general.created_by') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $journal->creator?->name ?? '-' }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-xl bg-white shadow-sm">
            <x-table>
                <x-table.thead>
                    <x-table.tr>
                        <x-table.th>{{ trans('double-entry::general.account_name') }}</x-table.th>
                        <x-table.th>{{ trans('double-entry::general.debit') }}</x-table.th>
                        <x-table.th>{{ trans('double-entry::general.credit') }}</x-table.th>
                        <x-table.th>{{ trans('general.description') }}</x-table.th>
                    </x-table.tr>
                </x-table.thead>
                <x-table.tbody>
                    @foreach ($journal->lines as $line)
                        <x-table.tr>
                            <x-table.td>{{ $line->account?->code ? $line->account->code . ' - ' . $line->account->name : $line->account_id }}</x-table.td>
                            <x-table.td>{{ number_format((float) $line->debit, 4) }}</x-table.td>
                            <x-table.td>{{ number_format((float) $line->credit, 4) }}</x-table.td>
                            <x-table.td>{{ $line->description ?: '-' }}</x-table.td>
                        </x-table.tr>
                    @endforeach
                </x-table.tbody>
            </x-table>
        </div>
    </div>
@endsection

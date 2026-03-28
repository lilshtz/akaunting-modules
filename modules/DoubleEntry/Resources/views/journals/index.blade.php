@extends('layouts.admin')

@section('title', trans('double-entry::general.journal_entries'))

@section('content')
    @php
        $sortLink = function (string $column) use ($sort, $direction) {
            $nextDirection = $sort === $column && $direction === 'asc' ? 'desc' : 'asc';

            return route('double-entry.journals.index', array_merge(request()->query(), [
                'sort' => $column,
                'direction' => $nextDirection,
            ]));
        };

        $statusClass = function (string $status): string {
            return $status === 'posted'
                ? 'bg-green-100 text-green-700'
                : 'bg-yellow-100 text-yellow-700';
        };
    @endphp

    <div class="space-y-6">
        <div class="flex items-center justify-between gap-3">
            <h1 class="text-xl font-semibold text-gray-900">{{ trans('double-entry::general.journal_entries') }}</h1>

            <a href="{{ route('double-entry.journals.create') }}" class="inline-flex items-center rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                {{ trans('general.title.new', ['type' => trans('double-entry::general.journal_entry')]) }}
            </a>
        </div>

        <div class="rounded-xl bg-white shadow-sm">
            <x-table>
                <x-table.thead>
                    <x-table.tr>
                        <x-table.th><a href="{{ $sortLink('date') }}">{{ trans('double-entry::general.date') }}</a></x-table.th>
                        <x-table.th><a href="{{ $sortLink('reference') }}">{{ trans('double-entry::general.reference') }}</a></x-table.th>
                        <x-table.th><a href="{{ $sortLink('description') }}">{{ trans('general.description') }}</a></x-table.th>
                        <x-table.th><a href="{{ $sortLink('total') }}">{{ trans('double-entry::general.total_amount') }}</a></x-table.th>
                        <x-table.th><a href="{{ $sortLink('status') }}">{{ trans('double-entry::general.status') }}</a></x-table.th>
                        <x-table.th>{{ trans('general.actions') }}</x-table.th>
                    </x-table.tr>
                </x-table.thead>

                <x-table.tbody>
                    @forelse ($journals as $journal)
                        <x-table.tr>
                            <x-table.td>{{ $journal->date?->format('Y-m-d') }}</x-table.td>
                            <x-table.td>
                                <a href="{{ route('double-entry.journals.show', $journal->id) }}" class="font-medium text-blue-600 hover:text-blue-800">
                                    {{ $journal->reference }}
                                </a>
                            </x-table.td>
                            <x-table.td>{{ \Illuminate\Support\Str::limit($journal->description, 60) ?: '-' }}</x-table.td>
                            <x-table.td>{{ number_format((float) $journal->total, 4) }}</x-table.td>
                            <x-table.td>
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium {{ $statusClass($journal->status) }}">
                                    {{ trans('double-entry::general.' . $journal->status) }}
                                </span>
                            </x-table.td>
                            <x-table.td>
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('double-entry.journals.show', $journal->id) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                        {{ trans('general.show') }}
                                    </a>

                                    @if ($journal->isEditable())
                                        <a href="{{ route('double-entry.journals.edit', $journal->id) }}" class="text-sm font-medium text-gray-700 hover:text-gray-900">
                                            {{ trans('general.edit') }}
                                        </a>

                                        <form action="{{ route('double-entry.journals.destroy', $journal->id) }}" method="POST" onsubmit="return confirm('{{ trans('messages.warning.confirm.delete') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-800">
                                                {{ trans('general.delete') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </x-table.td>
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

        @if (method_exists($journals, 'links'))
            <div>
                {{ $journals->links() }}
            </div>
        @endif
    </div>
@endsection

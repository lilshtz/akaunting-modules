<x-layouts.admin>
    <x-slot name="title">
        {{ trans('double-entry::general.journal_entries') }}
    </x-slot>

    <x-slot name="favorite"
        title="{{ trans('double-entry::general.journal_entries') }}"
        icon="book"
        route="double-entry.journals.index"
    ></x-slot>

    <x-slot name="buttons">
        <x-link href="{{ route('double-entry.journals.create') }}" kind="primary">
            {{ trans('general.title.new', ['type' => trans('double-entry::general.journal_entry')]) }}
        </x-link>
    </x-slot>

    <x-slot name="content">
        {{-- Filters --}}
        <div class="mb-4 bg-white rounded-xl shadow-sm p-4">
            <form method="GET" action="{{ route('double-entry.journals.index') }}" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.start_date') }}</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.end_date') }}</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}" class="rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('double-entry::general.reference') }}</label>
                    <input type="text" name="reference" value="{{ request('reference') }}" placeholder="{{ trans('double-entry::general.reference') }}" class="rounded-md border-gray-300 shadow-sm text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ trans('general.status') }}</label>
                    <select name="status" class="rounded-md border-gray-300 shadow-sm text-sm">
                        <option value="">{{ trans('general.all') }}</option>
                        <option value="draft" @selected(request('status') === 'draft')>{{ trans('double-entry::general.statuses.draft') }}</option>
                        <option value="posted" @selected(request('status') === 'posted')>{{ trans('double-entry::general.statuses.posted') }}</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white text-sm rounded-md hover:bg-purple-700">
                        {{ trans('general.filter') }}
                    </button>
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-xl shadow-sm">
            <table class="w-full">
                <thead>
                    <tr class="border-b">
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('general.date') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('double-entry::general.reference') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('general.description') }}</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-500">{{ trans('double-entry::general.debit') }}</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-500">{{ trans('double-entry::general.credit') }}</th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-gray-500">{{ trans('general.status') }}</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-500">{{ trans('general.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($journals as $journal)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm">
                                <a href="{{ route('double-entry.journals.show', $journal->id) }}" class="text-purple-700 hover:underline">
                                    {{ $journal->date->format('Y-m-d') }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-sm">{{ $journal->reference ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm">{{ Str::limit($journal->description, 60) }}</td>
                            <td class="px-4 py-3 text-sm text-right">{{ number_format($journal->total_debit, 2) }}</td>
                            <td class="px-4 py-3 text-sm text-right">{{ number_format($journal->total_credit, 2) }}</td>
                            <td class="px-4 py-3 text-sm text-center">
                                @if ($journal->status === 'posted')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ trans('double-entry::general.statuses.posted') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        {{ trans('double-entry::general.statuses.draft') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-right">
                                <x-dropdown id="dropdown-{{ $journal->id }}">
                                    <x-dropdown.link href="{{ route('double-entry.journals.show', $journal->id) }}">
                                        {{ trans('general.show') }}
                                    </x-dropdown.link>
                                    @if ($journal->status === 'draft')
                                        <x-dropdown.link href="{{ route('double-entry.journals.edit', $journal->id) }}">
                                            {{ trans('general.edit') }}
                                        </x-dropdown.link>
                                    @endif
                                    <x-dropdown.link href="{{ route('double-entry.journals.duplicate', $journal->id) }}"
                                        data-method="POST">
                                        {{ trans('general.duplicate') }}
                                    </x-dropdown.link>
                                    @if ($journal->status === 'draft')
                                        <x-delete-link :model="$journal" route="double-entry.journals.destroy" />
                                    @else
                                        <x-dropdown.link href="{{ route('double-entry.journals.destroy', $journal->id) }}"
                                            data-method="DELETE"
                                            data-confirm="{{ trans('double-entry::general.confirm_reverse') }}">
                                            {{ trans('double-entry::general.reverse') }}
                                        </x-dropdown.link>
                                    @endif
                                </x-dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">
                                {{ trans('general.no_records') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if ($journals->hasPages())
                <div class="px-4 py-3 border-t">
                    {{ $journals->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </x-slot>
</x-layouts.admin>

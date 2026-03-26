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
                    <label class="block text-sm font-medium text-gray-500 mb-1">{{ trans('general.date') }} {{ trans('general.from') }}</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="border rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">{{ trans('general.date') }} {{ trans('general.to') }}</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="border rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">{{ trans('double-entry::general.reference') }}</label>
                    <input type="text" name="reference" value="{{ request('reference') }}" class="border rounded px-3 py-2 text-sm" placeholder="{{ trans('double-entry::general.reference') }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">{{ trans('general.status') }}</label>
                    <select name="status" class="border rounded px-3 py-2 text-sm">
                        <option value="">{{ trans('general.all') }}</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>{{ trans('double-entry::general.statuses.draft') }}</option>
                        <option value="posted" {{ request('status') === 'posted' ? 'selected' : '' }}>{{ trans('double-entry::general.statuses.posted') }}</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="bg-purple-700 text-white px-4 py-2 rounded text-sm hover:bg-purple-800">{{ trans('general.search') }}</button>
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
                            <td class="px-4 py-3 text-sm">{{ $journal->date->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 text-sm">
                                <a href="{{ route('double-entry.journals.show', $journal->id) }}" class="text-purple-700 hover:underline">
                                    {{ $journal->reference ?? '-' }}
                                </a>
                            </td>
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
                                <x-dropdown id="dropdown-journal-{{ $journal->id }}">
                                    <x-dropdown.link href="{{ route('double-entry.journals.show', $journal->id) }}">
                                        {{ trans('general.show') }}
                                    </x-dropdown.link>
                                    @if ($journal->status === 'draft')
                                        <x-dropdown.link href="{{ route('double-entry.journals.edit', $journal->id) }}">
                                            {{ trans('general.edit') }}
                                        </x-dropdown.link>
                                    @endif
                                    <x-delete-link :model="$journal" route="double-entry.journals.destroy" />
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
        </div>

        @if ($journals->hasPages())
            <div class="mt-4">
                {{ $journals->withQueryString()->links() }}
            </div>
        @endif
    </x-slot>
</x-layouts.admin>

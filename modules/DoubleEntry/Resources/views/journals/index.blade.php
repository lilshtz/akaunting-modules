<x-layouts.admin>
    <x-slot name="title">{{ trans('double-entry::general.journal_entries') }}</x-slot>

    <x-slot name="favorite"
        title="{{ trans('double-entry::general.journal_entries') }}"
        icon="receipt_long"
        route="double-entry.journals.index"
    ></x-slot>

    <x-slot name="buttons">
        <x-link href="{{ route('double-entry.journals.create') }}" kind="primary">
            {{ trans('general.title.new', ['type' => trans('double-entry::general.journal')]) }}
        </x-link>
    </x-slot>

    <x-slot name="content">
        <x-table>
            <x-table.thead>
                <x-table.tr class="flex items-center px-1">
                    <x-table.th class="w-2/12">{{ trans('double-entry::general.journal_number') }}</x-table.th>
                    <x-table.th class="w-2/12">{{ trans('double-entry::general.journal_date') }}</x-table.th>
                    <x-table.th class="w-2/12">{{ trans('double-entry::general.reference') }}</x-table.th>
                    <x-table.th class="w-2/12">{{ trans('double-entry::general.status') }}</x-table.th>
                    <x-table.th class="w-2/12">{{ trans('double-entry::general.total_debits') }}</x-table.th>
                    <x-table.th class="w-1/12">{{ trans('double-entry::general.lines') }}</x-table.th>
                    <x-table.th class="w-1/12">{{ trans('general.actions') }}</x-table.th>
                </x-table.tr>
            </x-table.thead>
            <x-table.tbody>
                @forelse ($journals as $journal)
                    <x-table.tr>
                        <x-table.td class="w-2/12">
                            <a href="{{ route('double-entry.journals.show', $journal->id) }}" class="border-b">{{ $journal->number }}</a>
                        </x-table.td>
                        <x-table.td class="w-2/12">{{ optional($journal->date)->format('Y-m-d') }}</x-table.td>
                        <x-table.td class="w-2/12">{{ $journal->reference ?: trans('general.na') }}</x-table.td>
                        <x-table.td class="w-2/12">{{ trans('double-entry::general.' . $journal->status) }}</x-table.td>
                        <x-table.td class="w-2/12">@money($journal->total_debit, setting('default.currency', 'USD'), true)</x-table.td>
                        <x-table.td class="w-1/12">{{ $journal->lines_count }}</x-table.td>
                        <x-table.td class="w-1/12">
                            <x-dropdown id="journal-{{ $journal->id }}">
                                <x-dropdown.link href="{{ route('double-entry.journals.show', $journal->id) }}">
                                    {{ trans('general.view') }}
                                </x-dropdown.link>
                                @if ($journal->status === 'draft')
                                    <x-dropdown.link href="{{ route('double-entry.journals.edit', $journal->id) }}">
                                        {{ trans('general.edit') }}
                                    </x-dropdown.link>
                                    <x-delete-link :model="$journal" route="double-entry.journals.destroy" />
                                @endif
                            </x-dropdown>
                        </x-table.td>
                    </x-table.tr>
                @empty
                    <x-table.tr>
                        <x-table.td colspan="7">
                            <div class="flex justify-center py-4 text-gray-500">{{ trans('general.no_records') }}</div>
                        </x-table.td>
                    </x-table.tr>
                @endforelse
            </x-table.tbody>
        </x-table>

        <div class="mt-4">{{ $journals->links() }}</div>
    </x-slot>
</x-layouts.admin>

<x-layouts.admin>
    <x-slot name="title">{{ $journal->number }}</x-slot>

    <x-slot name="buttons">
        @if ($journal->status === 'draft')
            <x-link href="{{ route('double-entry.journals.edit', $journal->id) }}" kind="secondary">
                {{ trans('general.edit') }}
            </x-link>

            <x-form id="post-journal" method="POST" :route="['double-entry.journals.post', $journal->id]">
                <x-button type="submit">
                    {{ trans('double-entry::general.posted') }}
                </x-button>
            </x-form>
        @endif

        @if ($journal->status !== 'voided')
            <x-form id="void-journal" method="POST" :route="['double-entry.journals.void', $journal->id]">
                <x-button type="submit">
                    {{ trans('double-entry::general.voided') }}
                </x-button>
            </x-form>
        @endif
    </x-slot>

    <x-slot name="content">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="text-sm text-gray-500">{{ trans('double-entry::general.journal_date') }}</div>
                <div class="text-lg">{{ $journal->date->format('Y-m-d') }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="text-sm text-gray-500">{{ trans('double-entry::general.status') }}</div>
                <div class="text-lg">{{ trans('double-entry::general.' . $journal->status) }}</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="text-sm text-gray-500">{{ trans('double-entry::general.total_debits') }}</div>
                <div class="text-lg">@money($journal->total_debit, setting('default.currency', 'USD'), true)</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="text-sm text-gray-500">{{ trans('double-entry::general.total_credits') }}</div>
                <div class="text-lg">@money($journal->total_credit, setting('default.currency', 'USD'), true)</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <div class="text-sm text-gray-500 mb-1">{{ trans('double-entry::general.reference') }}</div>
            <div>{{ $journal->reference ?: trans('general.na') }}</div>
            <div class="text-sm text-gray-500 mt-4 mb-1">{{ trans('general.description') }}</div>
            <div>{{ $journal->description ?: trans('general.na') }}</div>
        </div>

        <x-table>
            <x-table.thead>
                <x-table.tr class="flex items-center px-1">
                    <x-table.th class="w-4/12">{{ trans('double-entry::general.account') }}</x-table.th>
                    <x-table.th class="w-3/12">{{ trans('double-entry::general.line_description') }}</x-table.th>
                    <x-table.th class="w-2/12">{{ trans('double-entry::general.debit') }}</x-table.th>
                    <x-table.th class="w-2/12">{{ trans('double-entry::general.credit') }}</x-table.th>
                    <x-table.th class="w-1/12">{{ trans('general.type') }}</x-table.th>
                </x-table.tr>
            </x-table.thead>
            <x-table.tbody>
                @foreach ($journal->lines as $line)
                    <x-table.tr>
                        <x-table.td class="w-4/12">{{ $line->account->code }} - {{ $line->account->name }}</x-table.td>
                        <x-table.td class="w-3/12">{{ $line->description ?: trans('general.na') }}</x-table.td>
                        <x-table.td class="w-2/12">{{ $line->entry_type === 'debit' ? money($line->amount, setting('default.currency', 'USD')) : '-' }}</x-table.td>
                        <x-table.td class="w-2/12">{{ $line->entry_type === 'credit' ? money($line->amount, setting('default.currency', 'USD')) : '-' }}</x-table.td>
                        <x-table.td class="w-1/12">{{ ucfirst($line->entry_type) }}</x-table.td>
                    </x-table.tr>
                @endforeach
            </x-table.tbody>
        </x-table>
    </x-slot>
</x-layouts.admin>

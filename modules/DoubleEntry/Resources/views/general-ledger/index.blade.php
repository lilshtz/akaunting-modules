<x-layouts.admin>
    <x-slot name="title">{{ trans('double-entry::general.general_ledger') }}</x-slot>

    <x-slot name="content">
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <form method="GET" action="{{ route('double-entry.general-ledger.index') }}" class="grid grid-cols-1 lg:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="text-sm">{{ trans('double-entry::general.account') }}</label>
                    <select name="account_id" class="w-full border rounded-lg px-3 py-2">
                        <option value="">All Accounts</option>
                        @foreach ($accounts as $id => $name)
                            <option value="{{ $id }}" {{ (string) ($filters['account_id'] ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm">{{ trans('double-entry::general.date_from') }}</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="text-sm">{{ trans('double-entry::general.date_to') }}</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full border rounded-lg px-3 py-2">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 border rounded-lg">Filter</button>
                    <button type="submit" name="format" value="csv" class="px-4 py-2 border rounded-lg">{{ trans('double-entry::general.csv') }}</button>
                    <button type="submit" name="format" value="pdf" class="px-4 py-2 border rounded-lg">{{ trans('double-entry::general.pdf') }}</button>
                </div>
            </form>
        </div>

        @foreach ($ledger as $section)
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                <div class="flex justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-semibold">{{ $section['account']->code }} - {{ $section['account']->name }}</h2>
                        <div class="text-sm text-gray-500">Opening: @money($section['opening_balance'], setting('default.currency', 'USD'), true)</div>
                    </div>
                    <div class="text-right text-sm text-gray-500">
                        Closing<br>
                        <span class="text-black font-semibold">@money($section['closing_balance'], setting('default.currency', 'USD'), true)</span>
                    </div>
                </div>

                <x-table>
                    <x-table.thead>
                        <x-table.tr class="flex items-center px-1">
                            <x-table.th class="w-1/12">{{ trans('general.date') }}</x-table.th>
                            <x-table.th class="w-2/12">{{ trans('double-entry::general.journal_number') }}</x-table.th>
                            <x-table.th class="w-2/12">{{ trans('double-entry::general.reference') }}</x-table.th>
                            <x-table.th class="w-3/12">{{ trans('general.description') }}</x-table.th>
                            <x-table.th class="w-1/12">{{ trans('double-entry::general.debit') }}</x-table.th>
                            <x-table.th class="w-1/12">{{ trans('double-entry::general.credit') }}</x-table.th>
                            <x-table.th class="w-2/12">{{ trans('double-entry::general.running_balance') }}</x-table.th>
                        </x-table.tr>
                    </x-table.thead>
                    <x-table.tbody>
                        @forelse ($section['lines'] as $line)
                            <x-table.tr>
                                <x-table.td class="w-1/12">{{ $line['date'] }}</x-table.td>
                                <x-table.td class="w-2/12">{{ $line['journal_number'] }}</x-table.td>
                                <x-table.td class="w-2/12">{{ $line['reference'] ?: trans('general.na') }}</x-table.td>
                                <x-table.td class="w-3/12">{{ $line['description'] ?: trans('general.na') }}</x-table.td>
                                <x-table.td class="w-1/12">{{ $line['debit'] ? money($line['debit'], setting('default.currency', 'USD')) : '-' }}</x-table.td>
                                <x-table.td class="w-1/12">{{ $line['credit'] ? money($line['credit'], setting('default.currency', 'USD')) : '-' }}</x-table.td>
                                <x-table.td class="w-2/12">{{ money($line['running_balance'], setting('default.currency', 'USD')) }}</x-table.td>
                            </x-table.tr>
                        @empty
                            <x-table.tr>
                                <x-table.td colspan="7"><div class="py-4 text-center text-gray-500">{{ trans('general.no_records') }}</div></x-table.td>
                            </x-table.tr>
                        @endforelse
                    </x-table.tbody>
                </x-table>
            </div>
        @endforeach
    </x-slot>
</x-layouts.admin>

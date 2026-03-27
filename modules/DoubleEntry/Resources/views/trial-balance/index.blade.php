<x-layouts.admin>
    <x-slot name="title">{{ trans('double-entry::general.trial_balance') }}</x-slot>

    <x-slot name="content">
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <form method="GET" action="{{ route('double-entry.trial-balance.index') }}" class="flex gap-4 items-end">
                <div>
                    <label class="text-sm">{{ trans('double-entry::general.date_to') }}</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="border rounded-lg px-3 py-2">
                </div>
                <button type="submit" class="px-4 py-2 border rounded-lg">Filter</button>
                <button type="submit" name="format" value="csv" class="px-4 py-2 border rounded-lg">{{ trans('double-entry::general.csv') }}</button>
                <button type="submit" name="format" value="pdf" class="px-4 py-2 border rounded-lg">{{ trans('double-entry::general.pdf') }}</button>
            </form>
        </div>

        <x-table>
            <x-table.thead>
                <x-table.tr class="flex items-center px-1">
                    <x-table.th class="w-6/12">{{ trans('double-entry::general.account') }}</x-table.th>
                    <x-table.th class="w-3/12">{{ trans('double-entry::general.debit') }}</x-table.th>
                    <x-table.th class="w-3/12">{{ trans('double-entry::general.credit') }}</x-table.th>
                </x-table.tr>
            </x-table.thead>
            <x-table.tbody>
                @foreach ($rows as $row)
                    <x-table.tr>
                        <x-table.td class="w-6/12">{{ $row['account']->code }} - {{ $row['account']->name }}</x-table.td>
                        <x-table.td class="w-3/12">{{ $row['debit'] ? money($row['debit'], setting('default.currency', 'USD')) : '-' }}</x-table.td>
                        <x-table.td class="w-3/12">{{ $row['credit'] ? money($row['credit'], setting('default.currency', 'USD')) : '-' }}</x-table.td>
                    </x-table.tr>
                @endforeach
                <x-table.tr>
                    <x-table.td class="w-6/12 font-semibold">Totals</x-table.td>
                    <x-table.td class="w-3/12 font-semibold">{{ money($rows->sum('debit'), setting('default.currency', 'USD')) }}</x-table.td>
                    <x-table.td class="w-3/12 font-semibold">{{ money($rows->sum('credit'), setting('default.currency', 'USD')) }}</x-table.td>
                </x-table.tr>
            </x-table.tbody>
        </x-table>
    </x-slot>
</x-layouts.admin>

<x-layouts.admin>
    <x-slot name="title">
        {{ trans('double-entry::general.general_ledger') }}
    </x-slot>

    <x-slot name="favorite"
        title="{{ trans('double-entry::general.general_ledger') }}"
        icon="menu_book"
        route="double-entry.general-ledger.index"
    ></x-slot>

    <x-slot name="buttons">
        <x-link href="{{ route('double-entry.general-ledger.export', array_merge(request()->query(), ['format' => 'csv'])) }}" kind="primary">
            {{ trans('double-entry::general.export_csv') }}
        </x-link>
        <x-link href="{{ route('double-entry.general-ledger.export', array_merge(request()->query(), ['format' => 'pdf'])) }}">
            {{ trans('double-entry::general.export_pdf') }}
        </x-link>
    </x-slot>

    <x-slot name="content">
        {{-- Filters --}}
        <div class="mb-4 bg-white rounded-xl shadow-sm p-4">
            <form method="GET" action="{{ route('double-entry.general-ledger.index') }}" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">{{ trans('double-entry::general.date_from') }}</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="border rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">{{ trans('double-entry::general.date_to') }}</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="border rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">{{ trans('double-entry::general.account') }}</label>
                    <select name="account_id" class="border rounded px-3 py-2 text-sm">
                        <option value="">{{ trans('double-entry::general.all_accounts') }}</option>
                        @foreach ($accountOptions as $id => $name)
                            <option value="{{ $id }}" {{ $accountId == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">{{ trans('double-entry::general.basis') }}</label>
                    <select name="basis" class="border rounded px-3 py-2 text-sm">
                        <option value="accrual" {{ $basis === 'accrual' ? 'selected' : '' }}>{{ trans('double-entry::general.bases.accrual') }}</option>
                        <option value="cash" {{ $basis === 'cash' ? 'selected' : '' }}>{{ trans('double-entry::general.bases.cash') }}</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="bg-purple-700 text-white px-4 py-2 rounded text-sm hover:bg-purple-800">{{ trans('general.search') }}</button>
                </div>
            </form>
        </div>

        {{-- Ledger Data --}}
        @forelse ($ledgerData as $accountData)
            <div class="mb-6 bg-white rounded-xl shadow-sm">
                <div class="px-4 py-3 border-b bg-gray-50 rounded-t-xl">
                    <h3 class="text-sm font-semibold">{{ $accountData['account']->display_name }}</h3>
                </div>
                <table class="w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ trans('general.date') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ trans('double-entry::general.reference') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ trans('general.description') }}</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">{{ trans('double-entry::general.debit') }}</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">{{ trans('double-entry::general.credit') }}</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">{{ trans('double-entry::general.running_balance') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b bg-gray-50">
                            <td colspan="5" class="px-4 py-2 text-xs font-medium text-gray-600">{{ trans('double-entry::general.opening') }}</td>
                            <td class="px-4 py-2 text-xs text-right font-medium">{{ number_format($accountData['opening_balance'], 2) }}</td>
                        </tr>
                        @foreach ($accountData['entries'] as $entry)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-2 text-xs">{{ $entry['date'] }}</td>
                                <td class="px-4 py-2 text-xs">
                                    <a href="{{ route('double-entry.journals.show', $entry['journal_id']) }}" class="text-purple-700 hover:underline">
                                        {{ $entry['reference'] ?? '-' }}
                                    </a>
                                </td>
                                <td class="px-4 py-2 text-xs">{{ $entry['description'] ?? '-' }}</td>
                                <td class="px-4 py-2 text-xs text-right">{{ $entry['debit'] > 0 ? number_format($entry['debit'], 2) : '' }}</td>
                                <td class="px-4 py-2 text-xs text-right">{{ $entry['credit'] > 0 ? number_format($entry['credit'], 2) : '' }}</td>
                                <td class="px-4 py-2 text-xs text-right font-medium">{{ number_format($entry['balance'], 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="border-t-2 bg-gray-50">
                            <td colspan="5" class="px-4 py-2 text-xs font-semibold text-gray-600">{{ trans('double-entry::general.closing') }}</td>
                            <td class="px-4 py-2 text-xs text-right font-semibold">{{ number_format($accountData['closing_balance'], 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @empty
            <div class="bg-white rounded-xl shadow-sm p-8 text-center text-sm text-gray-500">
                {{ trans('double-entry::general.no_transactions') }}
            </div>
        @endforelse
    </x-slot>
</x-layouts.admin>

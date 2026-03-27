<x-layouts.admin>
    <x-slot name="title">{{ trans('double-entry::general.general_ledger') }}</x-slot>

    <x-slot name="content">
        {{-- Filters --}}
        <div class="mb-6 rounded-xl bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('double-entry.general-ledger.index') }}" class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">{{ trans('general.start_date') }}</label>
                    <input type="date" name="start_date" value="{{ $startDate }}" class="rounded-lg border-gray-300 text-sm shadow-sm">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">{{ trans('general.end_date') }}</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="rounded-lg border-gray-300 text-sm shadow-sm">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">{{ trans('double-entry::general.account') }}</label>
                    <select name="account_id" class="rounded-lg border-gray-300 text-sm shadow-sm">
                        <option value="">{{ trans('general.all') }}</option>
                        @foreach($allAccounts as $acct)
                            <option value="{{ $acct->id }}" {{ $accountId == $acct->id ? 'selected' : '' }}>{{ $acct->code }} - {{ $acct->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">{{ trans('general.filter') }}</button>
            </form>
        </div>

        {{-- Ledger --}}
        <div class="space-y-6">
            @forelse($ledger as $entry)
                <div class="rounded-xl bg-white p-6 shadow-sm">
                    <h3 class="mb-3 text-sm font-semibold text-gray-900">
                        <span class="font-mono text-gray-500">{{ $entry['account']->code }}</span> {{ $entry['account']->name }}
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b text-gray-500">
                                    <th class="pb-2 pr-4">{{ trans('double-entry::general.date') }}</th>
                                    <th class="pb-2 pr-4">{{ trans('double-entry::general.number') }}</th>
                                    <th class="pb-2 pr-4">{{ trans('double-entry::general.description') }}</th>
                                    <th class="pb-2 pr-4 text-right">{{ trans('double-entry::general.debit') }}</th>
                                    <th class="pb-2 pr-4 text-right">{{ trans('double-entry::general.credit') }}</th>
                                    <th class="pb-2 text-right">{{ trans('double-entry::general.running_balance') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($entry['entries'] as $row)
                                    <tr class="border-b border-gray-100">
                                        <td class="py-2 pr-4">{{ $row['date']->format('Y-m-d') }}</td>
                                        <td class="py-2 pr-4 font-mono text-xs">{{ $row['number'] }}</td>
                                        <td class="py-2 pr-4 text-gray-600">{{ $row['description'] }}</td>
                                        <td class="py-2 pr-4 text-right font-mono">{{ $row['debit'] > 0 ? number_format($row['debit'], 2) : '' }}</td>
                                        <td class="py-2 pr-4 text-right font-mono">{{ $row['credit'] > 0 ? number_format($row['credit'], 2) : '' }}</td>
                                        <td class="py-2 text-right font-mono font-semibold">{{ number_format($row['balance'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="border-t font-semibold">
                                    <td colspan="3" class="py-2 pr-4 text-right">{{ trans('general.total') }}</td>
                                    <td class="py-2 pr-4 text-right font-mono">{{ number_format($entry['total_debit'], 2) }}</td>
                                    <td class="py-2 pr-4 text-right font-mono">{{ number_format($entry['total_credit'], 2) }}</td>
                                    <td class="py-2 text-right font-mono">{{ number_format($entry['closing_balance'], 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @empty
                <div class="rounded-xl bg-white p-12 text-center shadow-sm">
                    <p class="text-gray-500">{{ trans('general.no_records') }}</p>
                </div>
            @endforelse
        </div>
    </x-slot>
</x-layouts.admin>

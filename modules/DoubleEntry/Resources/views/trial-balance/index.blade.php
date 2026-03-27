<x-layouts.admin>
    <x-slot name="title">{{ trans('double-entry::general.trial_balance') }}</x-slot>

    <x-slot name="content">
        {{-- Filters --}}
        <div class="mb-6 rounded-xl bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('double-entry.trial-balance.index') }}" class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">{{ trans('general.start_date') }}</label>
                    <input type="date" name="start_date" value="{{ $startDate }}" class="rounded-lg border-gray-300 text-sm shadow-sm">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">{{ trans('general.end_date') }}</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="rounded-lg border-gray-300 text-sm shadow-sm">
                </div>
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">{{ trans('general.filter') }}</button>
            </form>
        </div>

        {{-- Trial Balance Table --}}
        <div class="rounded-xl bg-white p-6 shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b text-gray-500">
                            <th class="pb-3 pr-6">{{ trans('double-entry::general.code') }}</th>
                            <th class="pb-3 pr-6">{{ trans('double-entry::general.account') }}</th>
                            <th class="pb-3 pr-6 text-right">{{ trans('double-entry::general.debit') }}</th>
                            <th class="pb-3 text-right">{{ trans('double-entry::general.credit') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($trialBalance['rows'] as $row)
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-3 pr-6 font-mono text-sm text-gray-500">{{ $row['account']->code }}</td>
                                <td class="py-3 pr-6">{{ $row['account']->name }}</td>
                                <td class="py-3 pr-6 text-right font-mono">{{ $row['debit'] > 0 ? number_format($row['debit'], 2) : '' }}</td>
                                <td class="py-3 text-right font-mono">{{ $row['credit'] > 0 ? number_format($row['credit'], 2) : '' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-12 text-center text-gray-500">{{ trans('general.no_records') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($trialBalance['rows']))
                        <tfoot>
                            <tr class="border-t-2 font-bold">
                                <td colspan="2" class="py-3 pr-6 text-right">{{ trans('general.total') }}</td>
                                <td class="py-3 pr-6 text-right font-mono">{{ number_format($trialBalance['total_debit'], 2) }}</td>
                                <td class="py-3 text-right font-mono">{{ number_format($trialBalance['total_credit'], 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </x-slot>
</x-layouts.admin>

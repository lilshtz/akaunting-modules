<x-layouts.admin>
    <x-slot name="title">{{ trans('double-entry::general.profit_loss') }}</x-slot>

    <x-slot name="content">
        {{-- Filters --}}
        <div class="mb-6 rounded-xl bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('double-entry.profit-loss.index') }}" class="flex flex-wrap items-end gap-4">
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

        <div class="space-y-6">
            {{-- Income --}}
            <div class="rounded-xl bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">{{ trans('double-entry::general.total_income') }}</h3>
                <table class="w-full text-sm">
                    <tbody>
                        @foreach($profitLoss['income']['accounts'] as $row)
                            <tr class="border-b border-gray-100">
                                <td class="py-2">
                                    <span class="font-mono text-xs text-gray-500">{{ $row['account']->code }}</span>
                                    {{ $row['account']->name }}
                                </td>
                                <td class="py-2 text-right font-mono">{{ number_format($row['balance'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 font-bold">
                            <td class="py-3">{{ trans('double-entry::general.total_income') }}</td>
                            <td class="py-3 text-right font-mono">{{ number_format($profitLoss['income']['total'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Expenses --}}
            <div class="rounded-xl bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">{{ trans('double-entry::general.total_expenses') }}</h3>
                <table class="w-full text-sm">
                    <tbody>
                        @foreach($profitLoss['expense']['accounts'] as $row)
                            <tr class="border-b border-gray-100">
                                <td class="py-2">
                                    <span class="font-mono text-xs text-gray-500">{{ $row['account']->code }}</span>
                                    {{ $row['account']->name }}
                                </td>
                                <td class="py-2 text-right font-mono">{{ number_format($row['balance'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 font-bold">
                            <td class="py-3">{{ trans('double-entry::general.total_expenses') }}</td>
                            <td class="py-3 text-right font-mono">{{ number_format($profitLoss['expense']['total'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Net Profit --}}
            <div class="rounded-xl {{ $profitLoss['net_profit'] >= 0 ? 'bg-green-50' : 'bg-red-50' }} p-6">
                <div class="flex items-center justify-between text-lg font-bold">
                    <span>{{ trans('double-entry::general.net_profit') }}</span>
                    <span class="font-mono {{ $profitLoss['net_profit'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                        {{ number_format($profitLoss['net_profit'], 2) }}
                    </span>
                </div>
            </div>
        </div>
    </x-slot>
</x-layouts.admin>

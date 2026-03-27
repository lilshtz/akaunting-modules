<x-layouts.admin>
    <x-slot name="title">{{ trans('double-entry::general.balance_sheet') }}</x-slot>

    <x-slot name="content">
        {{-- Filters --}}
        <div class="mb-6 rounded-xl bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('double-entry.balance-sheet.index') }}" class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">{{ trans('general.date') }}</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="rounded-lg border-gray-300 text-sm shadow-sm">
                </div>
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">{{ trans('general.filter') }}</button>
            </form>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Assets --}}
            <div class="rounded-xl bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">{{ trans('double-entry::general.total_assets') }}</h3>
                <table class="w-full text-sm">
                    <tbody>
                        @foreach($balanceSheet['asset']['accounts'] as $row)
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
                            <td class="py-3">{{ trans('double-entry::general.total_assets') }}</td>
                            <td class="py-3 text-right font-mono">{{ number_format($balanceSheet['asset']['total'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Liabilities + Equity --}}
            <div class="space-y-6">
                <div class="rounded-xl bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900">{{ trans('double-entry::general.total_liabilities') }}</h3>
                    <table class="w-full text-sm">
                        <tbody>
                            @foreach($balanceSheet['liability']['accounts'] as $row)
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
                                <td class="py-3">{{ trans('double-entry::general.total_liabilities') }}</td>
                                <td class="py-3 text-right font-mono">{{ number_format($balanceSheet['liability']['total'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="rounded-xl bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900">{{ trans('double-entry::general.total_equity') }}</h3>
                    <table class="w-full text-sm">
                        <tbody>
                            @foreach($balanceSheet['equity']['accounts'] as $row)
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
                                <td class="py-3">{{ trans('double-entry::general.total_equity') }}</td>
                                <td class="py-3 text-right font-mono">{{ number_format($balanceSheet['equity']['total'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- L+E Total --}}
                <div class="rounded-xl bg-blue-50 p-4">
                    <div class="flex items-center justify-between font-bold">
                        <span>{{ trans('double-entry::general.total_liabilities') }} + {{ trans('double-entry::general.total_equity') }}</span>
                        <span class="font-mono">{{ number_format($balanceSheet['liability']['total'] + $balanceSheet['equity']['total'], 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>
</x-layouts.admin>

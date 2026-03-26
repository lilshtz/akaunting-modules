<x-layouts.admin>
    <x-slot name="title">
        {{ trans('double-entry::general.profit_loss') }}
    </x-slot>

    <x-slot name="favorite"
        title="{{ trans('double-entry::general.profit_loss') }}"
        icon="trending_up"
        route="double-entry.profit-loss.index"
    ></x-slot>

    <x-slot name="buttons">
        <x-link href="{{ route('double-entry.profit-loss.export', array_merge(request()->query(), ['format' => 'csv'])) }}">
            {{ trans('double-entry::general.export_csv') }}
        </x-link>
        <x-link href="{{ route('double-entry.profit-loss.export', array_merge(request()->query(), ['format' => 'pdf'])) }}">
            {{ trans('double-entry::general.export_pdf') }}
        </x-link>
    </x-slot>

    <x-slot name="content">
        {{-- Filters --}}
        <div class="mb-4 bg-white rounded-xl shadow-sm p-4">
            <form method="GET" action="{{ route('double-entry.profit-loss.index') }}" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">{{ trans('double-entry::general.date_from') }}</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="border rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">{{ trans('double-entry::general.date_to') }}</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="border rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">{{ trans('double-entry::general.basis') }}</label>
                    <select name="basis" class="border rounded px-3 py-2 text-sm">
                        <option value="accrual" {{ $basis === 'accrual' ? 'selected' : '' }}>{{ trans('double-entry::general.bases.accrual') }}</option>
                        <option value="cash" {{ $basis === 'cash' ? 'selected' : '' }}>{{ trans('double-entry::general.bases.cash') }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">{{ trans('double-entry::general.period_breakdown') }}</label>
                    <select name="breakdown" class="border rounded px-3 py-2 text-sm">
                        <option value="none" {{ $breakdown === 'none' ? 'selected' : '' }}>{{ trans('double-entry::general.no_breakdown') }}</option>
                        <option value="monthly" {{ $breakdown === 'monthly' ? 'selected' : '' }}>{{ trans('double-entry::general.frequencies.monthly') }}</option>
                        <option value="quarterly" {{ $breakdown === 'quarterly' ? 'selected' : '' }}>{{ trans('double-entry::general.frequencies.quarterly') }}</option>
                        <option value="annual" {{ $breakdown === 'annual' ? 'selected' : '' }}>{{ trans('double-entry::general.frequencies.yearly') }}</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="comparative" value="1" id="comparative" {{ $comparative ? 'checked' : '' }} class="rounded">
                    <label for="comparative" class="text-sm font-medium text-gray-500">{{ trans('double-entry::general.comparative') }}</label>
                </div>
                <div>
                    <button type="submit" class="bg-purple-700 text-white px-4 py-2 rounded text-sm hover:bg-purple-800">{{ trans('general.search') }}</button>
                </div>
            </form>
        </div>

        @if (!empty($periods))
            {{-- Period Breakdown View --}}
            <div class="bg-white rounded-xl shadow-sm">
                <div class="px-6 py-4 border-b text-center">
                    <h2 class="text-lg font-bold">{{ trans('double-entry::general.profit_loss') }}</h2>
                    <p class="text-sm text-gray-500">{{ $dateFrom }} to {{ $dateTo }} | {{ ucfirst($basis) }} | {{ ucfirst($breakdown) }}</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 min-w-[200px]">{{ trans('double-entry::general.account') }}</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-gray-500">Total</th>
                                @foreach ($periods as $period)
                                    <th class="px-4 py-3 text-right text-sm font-medium text-gray-500 whitespace-nowrap">{{ $period['label'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            {{-- INCOME --}}
                            <tr class="bg-gray-100">
                                <td colspan="{{ 2 + count($periods) }}" class="px-6 py-2 text-sm font-bold">{{ trans('double-entry::general.types.income') }}</td>
                            </tr>
                            @foreach ($data['income'] as $row)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-6 py-2 text-sm pl-8">{{ $row['account']->code }} - {{ $row['account']->name }}</td>
                                    <td class="px-4 py-2 text-sm text-right font-medium">{{ number_format($row['balance'], 2) }}</td>
                                    @foreach ($periods as $period)
                                        @php
                                            $pRow = collect($period['data']['income'])->firstWhere('account.id', $row['account']->id);
                                        @endphp
                                        <td class="px-4 py-2 text-sm text-right">{{ $pRow ? number_format($pRow['balance'], 2) : '-' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                            <tr class="border-b bg-green-50 font-semibold">
                                <td class="px-6 py-2 text-sm">{{ trans('double-entry::general.total_income') }}</td>
                                <td class="px-4 py-2 text-sm text-right">{{ number_format($data['total_income'], 2) }}</td>
                                @foreach ($periods as $period)
                                    <td class="px-4 py-2 text-sm text-right">{{ number_format($period['data']['total_income'], 2) }}</td>
                                @endforeach
                            </tr>

                            @if ($data['has_cogs'])
                                {{-- COGS --}}
                                <tr class="bg-gray-100">
                                    <td colspan="{{ 2 + count($periods) }}" class="px-6 py-2 text-sm font-bold">{{ trans('double-entry::general.cost_of_goods_sold') }}</td>
                                </tr>
                                @foreach ($data['cogs'] as $row)
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="px-6 py-2 text-sm pl-8">{{ $row['account']->code }} - {{ $row['account']->name }}</td>
                                        <td class="px-4 py-2 text-sm text-right font-medium">{{ number_format($row['balance'], 2) }}</td>
                                        @foreach ($periods as $period)
                                            @php $pRow = collect($period['data']['cogs'])->firstWhere('account.id', $row['account']->id); @endphp
                                            <td class="px-4 py-2 text-sm text-right">{{ $pRow ? number_format($pRow['balance'], 2) : '-' }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                                <tr class="border-b bg-blue-50 font-semibold">
                                    <td class="px-6 py-2 text-sm">{{ trans('double-entry::general.gross_profit') }}</td>
                                    <td class="px-4 py-2 text-sm text-right">{{ number_format($data['gross_profit'], 2) }}</td>
                                    @foreach ($periods as $period)
                                        <td class="px-4 py-2 text-sm text-right">{{ number_format($period['data']['gross_profit'], 2) }}</td>
                                    @endforeach
                                </tr>
                            @endif

                            {{-- EXPENSES --}}
                            <tr class="bg-gray-100">
                                <td colspan="{{ 2 + count($periods) }}" class="px-6 py-2 text-sm font-bold">{{ trans('double-entry::general.types.expense') }}</td>
                            </tr>
                            @foreach ($data['expenses'] as $row)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-6 py-2 text-sm pl-8">{{ $row['account']->code }} - {{ $row['account']->name }}</td>
                                    <td class="px-4 py-2 text-sm text-right font-medium">{{ number_format($row['balance'], 2) }}</td>
                                    @foreach ($periods as $period)
                                        @php $pRow = collect($period['data']['expenses'])->firstWhere('account.id', $row['account']->id); @endphp
                                        <td class="px-4 py-2 text-sm text-right">{{ $pRow ? number_format($pRow['balance'], 2) : '-' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                            <tr class="border-b bg-red-50 font-semibold">
                                <td class="px-6 py-2 text-sm">{{ trans('double-entry::general.total_expenses') }}</td>
                                <td class="px-4 py-2 text-sm text-right">{{ number_format($data['total_expenses'], 2) }}</td>
                                @foreach ($periods as $period)
                                    <td class="px-4 py-2 text-sm text-right">{{ number_format($period['data']['total_expenses'], 2) }}</td>
                                @endforeach
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 font-bold {{ $data['net_income'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                <td class="px-6 py-3 text-sm">
                                    {{ $data['net_income'] >= 0 ? trans('double-entry::general.net_profit') : trans('double-entry::general.net_loss') }}
                                </td>
                                <td class="px-4 py-3 text-sm text-right">{{ number_format($data['net_income'], 2) }}</td>
                                @foreach ($periods as $period)
                                    <td class="px-4 py-3 text-sm text-right">{{ number_format($period['data']['net_income'], 2) }}</td>
                                @endforeach
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @else
            {{-- Standard View --}}
            <div class="bg-white rounded-xl shadow-sm">
                <div class="px-6 py-4 border-b text-center">
                    <h2 class="text-lg font-bold">{{ trans('double-entry::general.profit_loss') }}</h2>
                    <p class="text-sm text-gray-500">{{ $dateFrom }} to {{ $dateTo }} | {{ ucfirst($basis) }}</p>
                </div>

                @php $colSpan = ($comparative && $priorData) ? 4 : 3; @endphp

                <table class="w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-500">{{ trans('double-entry::general.account') }}</th>
                            <th class="px-6 py-3 text-right text-sm font-medium text-gray-500">
                                {{ $comparative ? trans('double-entry::general.current_period') : trans('general.amount') }}
                            </th>
                            @if ($comparative && $priorData)
                                <th class="px-6 py-3 text-right text-sm font-medium text-gray-500">{{ trans('double-entry::general.prior_period') }}</th>
                            @endif
                            <th class="px-6 py-3 text-right text-sm font-medium text-gray-500">{{ trans('double-entry::general.percentage_of_income') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- INCOME --}}
                        <tr class="bg-gray-100">
                            <td colspan="{{ $colSpan }}" class="px-6 py-2 text-sm font-bold">{{ trans('double-entry::general.types.income') }}</td>
                        </tr>
                        @foreach ($data['income'] as $row)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-6 py-2 text-sm pl-8">{{ $row['account']->code }} - {{ $row['account']->name }}</td>
                                <td class="px-6 py-2 text-sm text-right">{{ number_format($row['balance'], 2) }}</td>
                                @if ($comparative && $priorData)
                                    @php
                                        $priorRow = collect($priorData['income'])->firstWhere('account.id', $row['account']->id);
                                    @endphp
                                    <td class="px-6 py-2 text-sm text-right">{{ $priorRow ? number_format($priorRow['balance'], 2) : '-' }}</td>
                                @endif
                                <td class="px-6 py-2 text-sm text-right text-gray-500">
                                    {{ $data['total_income'] > 0 ? number_format(($row['balance'] / $data['total_income']) * 100, 1) : '0' }}%
                                </td>
                            </tr>
                        @endforeach
                        <tr class="border-b bg-green-50 font-semibold">
                            <td class="px-6 py-2 text-sm">{{ trans('double-entry::general.total_income') }}</td>
                            <td class="px-6 py-2 text-sm text-right">{{ number_format($data['total_income'], 2) }}</td>
                            @if ($comparative && $priorData)
                                <td class="px-6 py-2 text-sm text-right">{{ number_format($priorData['total_income'], 2) }}</td>
                            @endif
                            <td class="px-6 py-2 text-sm text-right">100%</td>
                        </tr>

                        @if ($data['has_cogs'])
                            {{-- COGS --}}
                            <tr class="bg-gray-100">
                                <td colspan="{{ $colSpan }}" class="px-6 py-2 text-sm font-bold">{{ trans('double-entry::general.cost_of_goods_sold') }}</td>
                            </tr>
                            @foreach ($data['cogs'] as $row)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-6 py-2 text-sm pl-8">{{ $row['account']->code }} - {{ $row['account']->name }}</td>
                                    <td class="px-6 py-2 text-sm text-right">{{ number_format($row['balance'], 2) }}</td>
                                    @if ($comparative && $priorData)
                                        @php $priorRow = collect($priorData['cogs'])->firstWhere('account.id', $row['account']->id); @endphp
                                        <td class="px-6 py-2 text-sm text-right">{{ $priorRow ? number_format($priorRow['balance'], 2) : '-' }}</td>
                                    @endif
                                    <td class="px-6 py-2 text-sm text-right text-gray-500">
                                        {{ $data['total_income'] > 0 ? number_format(($row['balance'] / $data['total_income']) * 100, 1) : '0' }}%
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="border-b bg-blue-50 font-semibold">
                                <td class="px-6 py-2 text-sm">{{ trans('double-entry::general.gross_profit') }}</td>
                                <td class="px-6 py-2 text-sm text-right">{{ number_format($data['gross_profit'], 2) }}</td>
                                @if ($comparative && $priorData)
                                    <td class="px-6 py-2 text-sm text-right">{{ number_format($priorData['gross_profit'], 2) }}</td>
                                @endif
                                <td class="px-6 py-2 text-sm text-right">
                                    {{ $data['total_income'] > 0 ? number_format(($data['gross_profit'] / $data['total_income']) * 100, 1) : '0' }}%
                                </td>
                            </tr>
                        @endif

                        {{-- EXPENSES --}}
                        <tr class="bg-gray-100">
                            <td colspan="{{ $colSpan }}" class="px-6 py-2 text-sm font-bold">{{ trans('double-entry::general.types.expense') }}</td>
                        </tr>
                        @foreach ($data['expenses'] as $row)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-6 py-2 text-sm pl-8">{{ $row['account']->code }} - {{ $row['account']->name }}</td>
                                <td class="px-6 py-2 text-sm text-right">{{ number_format($row['balance'], 2) }}</td>
                                @if ($comparative && $priorData)
                                    @php
                                        $priorRow = collect($priorData['expenses'])->firstWhere('account.id', $row['account']->id);
                                    @endphp
                                    <td class="px-6 py-2 text-sm text-right">{{ $priorRow ? number_format($priorRow['balance'], 2) : '-' }}</td>
                                @endif
                                <td class="px-6 py-2 text-sm text-right text-gray-500">
                                    {{ $data['total_income'] > 0 ? number_format(($row['balance'] / $data['total_income']) * 100, 1) : '0' }}%
                                </td>
                            </tr>
                        @endforeach
                        <tr class="border-b bg-red-50 font-semibold">
                            <td class="px-6 py-2 text-sm">{{ trans('double-entry::general.total_expenses') }}</td>
                            <td class="px-6 py-2 text-sm text-right">{{ number_format($data['total_expenses'], 2) }}</td>
                            @if ($comparative && $priorData)
                                <td class="px-6 py-2 text-sm text-right">{{ number_format($priorData['total_expenses'], 2) }}</td>
                            @endif
                            <td class="px-6 py-2 text-sm text-right">
                                {{ $data['total_income'] > 0 ? number_format(($data['total_expenses'] / $data['total_income']) * 100, 1) : '0' }}%
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 font-bold {{ $data['net_income'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                            <td class="px-6 py-3 text-sm">
                                {{ $data['net_income'] >= 0 ? trans('double-entry::general.net_profit') : trans('double-entry::general.net_loss') }}
                            </td>
                            <td class="px-6 py-3 text-sm text-right">{{ number_format($data['net_income'], 2) }}</td>
                            @if ($comparative && $priorData)
                                <td class="px-6 py-3 text-sm text-right">{{ number_format($priorData['net_income'], 2) }}</td>
                            @endif
                            <td class="px-6 py-3 text-sm text-right">
                                {{ $data['total_income'] > 0 ? number_format(($data['net_income'] / $data['total_income']) * 100, 1) : '0' }}%
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </x-slot>
</x-layouts.admin>

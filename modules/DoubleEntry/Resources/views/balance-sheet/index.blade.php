<x-layouts.admin>
    <x-slot name="title">
        {{ trans('double-entry::general.balance_sheet') }}
    </x-slot>

    <x-slot name="favorite"
        title="{{ trans('double-entry::general.balance_sheet') }}"
        icon="account_balance"
        route="double-entry.balance-sheet.index"
    ></x-slot>

    <x-slot name="buttons">
        <x-link href="{{ route('double-entry.balance-sheet.export', array_merge(request()->query(), ['format' => 'csv'])) }}">
            {{ trans('double-entry::general.export_csv') }}
        </x-link>
        <x-link href="{{ route('double-entry.balance-sheet.export', array_merge(request()->query(), ['format' => 'pdf'])) }}">
            {{ trans('double-entry::general.export_pdf') }}
        </x-link>
    </x-slot>

    <x-slot name="content">
        {{-- Filters --}}
        <div class="mb-4 bg-white rounded-xl shadow-sm p-4">
            <form method="GET" action="{{ route('double-entry.balance-sheet.index') }}" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">{{ trans('double-entry::general.as_of_date') }}</label>
                    <input type="date" name="as_of_date" value="{{ $asOfDate }}" class="border rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">{{ trans('double-entry::general.basis') }}</label>
                    <select name="basis" class="border rounded px-3 py-2 text-sm">
                        <option value="accrual" {{ $basis === 'accrual' ? 'selected' : '' }}>{{ trans('double-entry::general.bases.accrual') }}</option>
                        <option value="cash" {{ $basis === 'cash' ? 'selected' : '' }}>{{ trans('double-entry::general.bases.cash') }}</option>
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

        {{-- Balance Sheet --}}
        <div class="bg-white rounded-xl shadow-sm">
            <div class="px-6 py-4 border-b text-center">
                <h2 class="text-lg font-bold">{{ trans('double-entry::general.balance_sheet') }}</h2>
                <p class="text-sm text-gray-500">{{ trans('double-entry::general.as_of_date') }}: {{ $asOfDate }} | {{ ucfirst($basis) }}</p>
            </div>

            @php $colSpan = ($comparative && $priorData) ? 3 : 2; @endphp

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
                    </tr>
                </thead>
                <tbody>
                    {{-- ASSETS --}}
                    <tr class="bg-gray-100">
                        <td colspan="{{ $colSpan }}" class="px-6 py-2 text-sm font-bold">{{ trans('double-entry::general.types.asset') }}</td>
                    </tr>
                    @foreach ($data['assets'] as $groupIdx => $group)
                        @if (!empty($group['label']))
                            <tr class="bg-gray-50">
                                <td colspan="{{ $colSpan }}" class="px-6 py-1 text-sm font-semibold text-gray-600 pl-8">{{ $group['label'] }}</td>
                            </tr>
                        @endif
                        @foreach ($group['accounts'] as $row)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-6 py-2 text-sm {{ $row['account']->parent_id ?? false ? 'pl-14' : 'pl-8' }}">
                                    @if (is_object($row['account']) && isset($row['account']->code) && $row['account']->code)
                                        {{ $row['account']->code }} -
                                    @endif
                                    {{ $row['account']->name }}
                                </td>
                                <td class="px-6 py-2 text-sm text-right">{{ number_format($row['balance'], 2) }}</td>
                                @if ($comparative && $priorData)
                                    @php
                                        $priorBalance = '-';
                                        foreach ($priorData['assets'] as $pg) {
                                            $pr = collect($pg['accounts'])->firstWhere('account.id', $row['account']->id ?? null);
                                            if ($pr) { $priorBalance = number_format($pr['balance'], 2); break; }
                                        }
                                    @endphp
                                    <td class="px-6 py-2 text-sm text-right">{{ $priorBalance }}</td>
                                @endif
                            </tr>
                        @endforeach
                        @if (!empty($group['label']) && $group['subtotal'] != 0)
                            <tr class="border-b bg-gray-50">
                                <td class="px-6 py-1 text-xs font-medium text-gray-500 pl-10">Subtotal: {{ $group['label'] }}</td>
                                <td class="px-6 py-1 text-xs text-right text-gray-500">{{ number_format($group['subtotal'], 2) }}</td>
                                @if ($comparative && $priorData)
                                    <td class="px-6 py-1 text-xs text-right text-gray-500">
                                        @php
                                            $priorGroup = collect($priorData['assets'])->firstWhere('label', $group['label']);
                                        @endphp
                                        {{ $priorGroup ? number_format($priorGroup['subtotal'], 2) : '-' }}
                                    </td>
                                @endif
                            </tr>
                        @endif
                    @endforeach
                    <tr class="border-b bg-gray-50 font-semibold">
                        <td class="px-6 py-2 text-sm">{{ trans('double-entry::general.total_assets') }}</td>
                        <td class="px-6 py-2 text-sm text-right">{{ number_format($data['total_assets'], 2) }}</td>
                        @if ($comparative && $priorData)
                            <td class="px-6 py-2 text-sm text-right">{{ number_format($priorData['total_assets'], 2) }}</td>
                        @endif
                    </tr>

                    {{-- LIABILITIES --}}
                    <tr class="bg-gray-100">
                        <td colspan="{{ $colSpan }}" class="px-6 py-2 text-sm font-bold">{{ trans('double-entry::general.types.liability') }}</td>
                    </tr>
                    @foreach ($data['liabilities'] as $group)
                        @if (!empty($group['label']))
                            <tr class="bg-gray-50">
                                <td colspan="{{ $colSpan }}" class="px-6 py-1 text-sm font-semibold text-gray-600 pl-8">{{ $group['label'] }}</td>
                            </tr>
                        @endif
                        @foreach ($group['accounts'] as $row)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-6 py-2 text-sm {{ $row['account']->parent_id ?? false ? 'pl-14' : 'pl-8' }}">
                                    @if (is_object($row['account']) && isset($row['account']->code) && $row['account']->code)
                                        {{ $row['account']->code }} -
                                    @endif
                                    {{ $row['account']->name }}
                                </td>
                                <td class="px-6 py-2 text-sm text-right">{{ number_format($row['balance'], 2) }}</td>
                                @if ($comparative && $priorData)
                                    @php
                                        $priorBalance = '-';
                                        foreach ($priorData['liabilities'] as $pg) {
                                            $pr = collect($pg['accounts'])->firstWhere('account.id', $row['account']->id ?? null);
                                            if ($pr) { $priorBalance = number_format($pr['balance'], 2); break; }
                                        }
                                    @endphp
                                    <td class="px-6 py-2 text-sm text-right">{{ $priorBalance }}</td>
                                @endif
                            </tr>
                        @endforeach
                        @if (!empty($group['label']) && $group['subtotal'] != 0)
                            <tr class="border-b bg-gray-50">
                                <td class="px-6 py-1 text-xs font-medium text-gray-500 pl-10">Subtotal: {{ $group['label'] }}</td>
                                <td class="px-6 py-1 text-xs text-right text-gray-500">{{ number_format($group['subtotal'], 2) }}</td>
                                @if ($comparative && $priorData)
                                    <td class="px-6 py-1 text-xs text-right text-gray-500">
                                        @php $priorGroup = collect($priorData['liabilities'])->firstWhere('label', $group['label']); @endphp
                                        {{ $priorGroup ? number_format($priorGroup['subtotal'], 2) : '-' }}
                                    </td>
                                @endif
                            </tr>
                        @endif
                    @endforeach
                    <tr class="border-b bg-gray-50 font-semibold">
                        <td class="px-6 py-2 text-sm">{{ trans('double-entry::general.total_liabilities') }}</td>
                        <td class="px-6 py-2 text-sm text-right">{{ number_format($data['total_liabilities'], 2) }}</td>
                        @if ($comparative && $priorData)
                            <td class="px-6 py-2 text-sm text-right">{{ number_format($priorData['total_liabilities'], 2) }}</td>
                        @endif
                    </tr>

                    {{-- EQUITY --}}
                    <tr class="bg-gray-100">
                        <td colspan="{{ $colSpan }}" class="px-6 py-2 text-sm font-bold">{{ trans('double-entry::general.types.equity') }}</td>
                    </tr>
                    @foreach ($data['equity'] as $group)
                        @if (!empty($group['label']))
                            <tr class="bg-gray-50">
                                <td colspan="{{ $colSpan }}" class="px-6 py-1 text-sm font-semibold text-gray-600 pl-8">{{ $group['label'] }}</td>
                            </tr>
                        @endif
                        @foreach ($group['accounts'] as $row)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-6 py-2 text-sm {{ $row['account']->parent_id ?? false ? 'pl-14' : 'pl-8' }}">
                                    @if (is_object($row['account']) && isset($row['account']->code) && $row['account']->code)
                                        {{ $row['account']->code }} -
                                    @endif
                                    {{ $row['account']->name }}
                                </td>
                                <td class="px-6 py-2 text-sm text-right">{{ number_format($row['balance'], 2) }}</td>
                                @if ($comparative && $priorData)
                                    @php
                                        $priorBalance = '-';
                                        foreach ($priorData['equity'] as $pg) {
                                            $pr = collect($pg['accounts'])->firstWhere('account.id', $row['account']->id ?? null);
                                            if ($pr) { $priorBalance = number_format($pr['balance'], 2); break; }
                                        }
                                    @endphp
                                    <td class="px-6 py-2 text-sm text-right">{{ $priorBalance }}</td>
                                @endif
                            </tr>
                        @endforeach
                        @if (!empty($group['label']) && $group['subtotal'] != 0)
                            <tr class="border-b bg-gray-50">
                                <td class="px-6 py-1 text-xs font-medium text-gray-500 pl-10">Subtotal: {{ $group['label'] }}</td>
                                <td class="px-6 py-1 text-xs text-right text-gray-500">{{ number_format($group['subtotal'], 2) }}</td>
                                @if ($comparative && $priorData)
                                    <td class="px-6 py-1 text-xs text-right text-gray-500">
                                        @php $priorGroup = collect($priorData['equity'])->firstWhere('label', $group['label']); @endphp
                                        {{ $priorGroup ? number_format($priorGroup['subtotal'], 2) : '-' }}
                                    </td>
                                @endif
                            </tr>
                        @endif
                    @endforeach
                    <tr class="border-b bg-gray-50 font-semibold">
                        <td class="px-6 py-2 text-sm">{{ trans('double-entry::general.total_equity') }}</td>
                        <td class="px-6 py-2 text-sm text-right">{{ number_format($data['total_equity'], 2) }}</td>
                        @if ($comparative && $priorData)
                            <td class="px-6 py-2 text-sm text-right">{{ number_format($priorData['total_equity'], 2) }}</td>
                        @endif
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="border-t-2 font-bold">
                        <td class="px-6 py-3 text-sm">{{ trans('double-entry::general.total_liabilities_equity') }}</td>
                        <td class="px-6 py-3 text-sm text-right">{{ number_format($data['total_liabilities'] + $data['total_equity'], 2) }}</td>
                        @if ($comparative && $priorData)
                            <td class="px-6 py-3 text-sm text-right">{{ number_format($priorData['total_liabilities'] + $priorData['total_equity'], 2) }}</td>
                        @endif
                    </tr>
                    <tr>
                        <td colspan="{{ $colSpan }}" class="px-6 py-2 text-center text-xs {{ $data['is_balanced'] ? 'text-green-600' : 'text-red-600' }} font-medium">
                            @if ($data['is_balanced'])
                                &#10003; {{ trans('double-entry::general.assets_equal_liabilities_equity') }}
                            @else
                                &#10007; {{ trans('double-entry::general.assets_equal_liabilities_equity') }} — Difference: {{ number_format(abs($data['total_assets'] - $data['total_liabilities'] - $data['total_equity']), 2) }}
                            @endif
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-slot>
</x-layouts.admin>

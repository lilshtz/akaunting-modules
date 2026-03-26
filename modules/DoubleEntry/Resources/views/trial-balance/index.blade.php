<x-layouts.admin>
    <x-slot name="title">
        {{ trans('double-entry::general.trial_balance') }}
    </x-slot>

    <x-slot name="favorite"
        title="{{ trans('double-entry::general.trial_balance') }}"
        icon="balance"
        route="double-entry.trial-balance.index"
    ></x-slot>

    <x-slot name="buttons">
        <x-link href="{{ route('double-entry.trial-balance.export', array_merge(request()->query(), ['format' => 'csv'])) }}" kind="primary">
            {{ trans('double-entry::general.export_csv') }}
        </x-link>
        <x-link href="{{ route('double-entry.trial-balance.export', array_merge(request()->query(), ['format' => 'pdf'])) }}">
            {{ trans('double-entry::general.export_pdf') }}
        </x-link>
    </x-slot>

    <x-slot name="content">
        {{-- Filters --}}
        <div class="mb-4 bg-white rounded-xl shadow-sm p-4">
            <form method="GET" action="{{ route('double-entry.trial-balance.index') }}" class="flex flex-wrap gap-4 items-end">
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
                    <button type="submit" class="bg-purple-700 text-white px-4 py-2 rounded text-sm hover:bg-purple-800">{{ trans('general.search') }}</button>
                </div>
            </form>
        </div>

        {{-- Trial Balance Table --}}
        <div class="bg-white rounded-xl shadow-sm">
            <table class="w-full">
                <thead>
                    <tr class="border-b">
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('double-entry::general.account_code') }}</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">{{ trans('double-entry::general.account_name') }}</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-500">{{ trans('double-entry::general.debit') }}</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-500">{{ trans('double-entry::general.credit') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($types as $type)
                        @if (isset($trialBalance['accounts'][$type]))
                            <tr class="bg-gray-50">
                                <td colspan="4" class="px-4 py-2 text-sm font-semibold capitalize">{{ trans('double-entry::general.types.' . $type) }}</td>
                            </tr>
                            @php $typeDebit = 0; $typeCredit = 0; @endphp
                            @foreach ($trialBalance['accounts'][$type] as $row)
                                @php $typeDebit += $row['debit']; $typeCredit += $row['credit']; @endphp
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-4 py-2 text-sm pl-8">{{ $row['account']->code }}</td>
                                    <td class="px-4 py-2 text-sm">{{ $row['account']->name }}</td>
                                    <td class="px-4 py-2 text-sm text-right">{{ $row['debit'] > 0 ? number_format($row['debit'], 2) : '' }}</td>
                                    <td class="px-4 py-2 text-sm text-right">{{ $row['credit'] > 0 ? number_format($row['credit'], 2) : '' }}</td>
                                </tr>
                            @endforeach
                            <tr class="border-b bg-gray-100">
                                <td colspan="2" class="px-4 py-2 text-sm font-medium text-right capitalize">{{ trans('general.total') }} {{ trans('double-entry::general.types.' . $type) }}</td>
                                <td class="px-4 py-2 text-sm text-right font-medium">{{ $typeDebit > 0 ? number_format($typeDebit, 2) : '' }}</td>
                                <td class="px-4 py-2 text-sm text-right font-medium">{{ $typeCredit > 0 ? number_format($typeCredit, 2) : '' }}</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 font-bold">
                        <td colspan="2" class="px-4 py-3 text-sm text-right">{{ trans('double-entry::general.grand_total') }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ number_format($trialBalance['grand_debit'], 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ number_format($trialBalance['grand_credit'], 2) }}</td>
                    </tr>
                    @if (round($trialBalance['grand_debit'], 2) === round($trialBalance['grand_credit'], 2))
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-center text-xs text-green-600 font-medium">
                                &#10003; {{ trans('double-entry::general.balanced') }}
                            </td>
                        </tr>
                    @else
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-center text-xs text-red-600 font-medium">
                                &#10007; {{ trans('double-entry::general.unbalanced') }}
                            </td>
                        </tr>
                    @endif
                </tfoot>
            </table>
        </div>
    </x-slot>
</x-layouts.admin>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Balance Sheet</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; }
        h1 { font-size: 16px; margin-bottom: 5px; text-align: center; }
        .meta { color: #666; margin-bottom: 15px; text-align: center; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 4px 8px; text-align: left; }
        th { background: #f5f5f5; font-weight: bold; }
        .text-right { text-align: right; }
        .section-header { background: #e8e8e8; font-weight: bold; }
        .group-header { background: #f0f0f0; font-weight: 600; padding-left: 16px; font-size: 9px; color: #555; }
        .subtotal { background: #f8f8f8; font-weight: 600; font-size: 9px; color: #555; }
        .section-total { background: #f0f0f0; font-weight: bold; }
        .grand-total { background: #ddd; font-weight: bold; }
        .indent { padding-left: 20px; }
        .indent-deep { padding-left: 32px; }
        .balanced { color: #16a34a; text-align: center; font-size: 9px; margin-top: 8px; }
        .unbalanced { color: #dc2626; text-align: center; font-size: 9px; margin-top: 8px; }
    </style>
</head>
<body>
    <h1>Balance Sheet</h1>
    <div class="meta">As of {{ $asOfDate }} | Basis: {{ ucfirst($basis) }}</div>

    <table>
        <thead>
            <tr>
                <th>Account</th>
                <th class="text-right">{{ $comparative && $priorData ? 'Current' : 'Amount' }}</th>
                @if ($comparative && $priorData)
                    <th class="text-right">Prior Year</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @php $colSpan = ($comparative && $priorData) ? 3 : 2; @endphp

            {{-- ASSETS --}}
            <tr class="section-header"><td colspan="{{ $colSpan }}">Assets</td></tr>
            @foreach ($data['assets'] as $group)
                @if (!empty($group['label']))
                    <tr class="group-header"><td colspan="{{ $colSpan }}">{{ $group['label'] }}</td></tr>
                @endif
                @foreach ($group['accounts'] as $row)
                    <tr>
                        <td class="{{ $row['account']->parent_id ?? false ? 'indent-deep' : 'indent' }}">
                            @if (isset($row['account']->code) && $row['account']->code){{ $row['account']->code }} - @endif{{ $row['account']->name }}
                        </td>
                        <td class="text-right">{{ number_format($row['balance'], 2) }}</td>
                        @if ($comparative && $priorData)
                            @php
                                $priorBalance = '-';
                                foreach ($priorData['assets'] as $pg) {
                                    $pr = collect($pg['accounts'])->firstWhere('account.id', $row['account']->id ?? null);
                                    if ($pr) { $priorBalance = number_format($pr['balance'], 2); break; }
                                }
                            @endphp
                            <td class="text-right">{{ $priorBalance }}</td>
                        @endif
                    </tr>
                @endforeach
                @if (!empty($group['label']) && $group['subtotal'] != 0)
                    <tr class="subtotal">
                        <td class="indent">Subtotal: {{ $group['label'] }}</td>
                        <td class="text-right">{{ number_format($group['subtotal'], 2) }}</td>
                        @if ($comparative && $priorData)
                            @php $priorGroup = collect($priorData['assets'])->firstWhere('label', $group['label']); @endphp
                            <td class="text-right">{{ $priorGroup ? number_format($priorGroup['subtotal'], 2) : '-' }}</td>
                        @endif
                    </tr>
                @endif
            @endforeach
            <tr class="section-total">
                <td>Total Assets</td>
                <td class="text-right">{{ number_format($data['total_assets'], 2) }}</td>
                @if ($comparative && $priorData)
                    <td class="text-right">{{ number_format($priorData['total_assets'], 2) }}</td>
                @endif
            </tr>

            {{-- LIABILITIES --}}
            <tr class="section-header"><td colspan="{{ $colSpan }}">Liabilities</td></tr>
            @foreach ($data['liabilities'] as $group)
                @if (!empty($group['label']))
                    <tr class="group-header"><td colspan="{{ $colSpan }}">{{ $group['label'] }}</td></tr>
                @endif
                @foreach ($group['accounts'] as $row)
                    <tr>
                        <td class="{{ $row['account']->parent_id ?? false ? 'indent-deep' : 'indent' }}">
                            @if (isset($row['account']->code) && $row['account']->code){{ $row['account']->code }} - @endif{{ $row['account']->name }}
                        </td>
                        <td class="text-right">{{ number_format($row['balance'], 2) }}</td>
                        @if ($comparative && $priorData)
                            @php
                                $priorBalance = '-';
                                foreach ($priorData['liabilities'] as $pg) {
                                    $pr = collect($pg['accounts'])->firstWhere('account.id', $row['account']->id ?? null);
                                    if ($pr) { $priorBalance = number_format($pr['balance'], 2); break; }
                                }
                            @endphp
                            <td class="text-right">{{ $priorBalance }}</td>
                        @endif
                    </tr>
                @endforeach
                @if (!empty($group['label']) && $group['subtotal'] != 0)
                    <tr class="subtotal">
                        <td class="indent">Subtotal: {{ $group['label'] }}</td>
                        <td class="text-right">{{ number_format($group['subtotal'], 2) }}</td>
                        @if ($comparative && $priorData)
                            @php $priorGroup = collect($priorData['liabilities'])->firstWhere('label', $group['label']); @endphp
                            <td class="text-right">{{ $priorGroup ? number_format($priorGroup['subtotal'], 2) : '-' }}</td>
                        @endif
                    </tr>
                @endif
            @endforeach
            <tr class="section-total">
                <td>Total Liabilities</td>
                <td class="text-right">{{ number_format($data['total_liabilities'], 2) }}</td>
                @if ($comparative && $priorData)
                    <td class="text-right">{{ number_format($priorData['total_liabilities'], 2) }}</td>
                @endif
            </tr>

            {{-- EQUITY --}}
            <tr class="section-header"><td colspan="{{ $colSpan }}">Equity</td></tr>
            @foreach ($data['equity'] as $group)
                @if (!empty($group['label']))
                    <tr class="group-header"><td colspan="{{ $colSpan }}">{{ $group['label'] }}</td></tr>
                @endif
                @foreach ($group['accounts'] as $row)
                    <tr>
                        <td class="{{ $row['account']->parent_id ?? false ? 'indent-deep' : 'indent' }}">
                            @if (isset($row['account']->code) && $row['account']->code){{ $row['account']->code }} - @endif{{ $row['account']->name }}
                        </td>
                        <td class="text-right">{{ number_format($row['balance'], 2) }}</td>
                        @if ($comparative && $priorData)
                            @php
                                $priorBalance = '-';
                                foreach ($priorData['equity'] as $pg) {
                                    $pr = collect($pg['accounts'])->firstWhere('account.id', $row['account']->id ?? null);
                                    if ($pr) { $priorBalance = number_format($pr['balance'], 2); break; }
                                }
                            @endphp
                            <td class="text-right">{{ $priorBalance }}</td>
                        @endif
                    </tr>
                @endforeach
                @if (!empty($group['label']) && $group['subtotal'] != 0)
                    <tr class="subtotal">
                        <td class="indent">Subtotal: {{ $group['label'] }}</td>
                        <td class="text-right">{{ number_format($group['subtotal'], 2) }}</td>
                        @if ($comparative && $priorData)
                            @php $priorGroup = collect($priorData['equity'])->firstWhere('label', $group['label']); @endphp
                            <td class="text-right">{{ $priorGroup ? number_format($priorGroup['subtotal'], 2) : '-' }}</td>
                        @endif
                    </tr>
                @endif
            @endforeach
            <tr class="section-total">
                <td>Total Equity</td>
                <td class="text-right">{{ number_format($data['total_equity'], 2) }}</td>
                @if ($comparative && $priorData)
                    <td class="text-right">{{ number_format($priorData['total_equity'], 2) }}</td>
                @endif
            </tr>
        </tbody>
        <tfoot>
            <tr class="grand-total">
                <td>Total Liabilities & Equity</td>
                <td class="text-right">{{ number_format($data['total_liabilities'] + $data['total_equity'], 2) }}</td>
                @if ($comparative && $priorData)
                    <td class="text-right">{{ number_format($priorData['total_liabilities'] + $priorData['total_equity'], 2) }}</td>
                @endif
            </tr>
        </tfoot>
    </table>

    <div class="{{ $data['is_balanced'] ? 'balanced' : 'unbalanced' }}">
        @if ($data['is_balanced'])
            Assets = Liabilities + Equity &#10003;
        @else
            Assets &#8800; Liabilities + Equity — Difference: {{ number_format(abs($data['total_assets'] - $data['total_liabilities'] - $data['total_equity']), 2) }}
        @endif
    </div>
</body>
</html>

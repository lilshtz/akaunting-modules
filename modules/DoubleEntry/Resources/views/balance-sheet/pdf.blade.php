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
        .subtotal { background: #f0f0f0; font-weight: bold; }
        .grand-total { background: #ddd; font-weight: bold; }
        .indent { padding-left: 20px; }
    </style>
</head>
<body>
    <h1>Balance Sheet</h1>
    <div class="meta">As of {{ $asOfDate }} | Basis: {{ ucfirst($basis) }}</div>

    <table>
        <thead>
            <tr>
                <th>Account</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr class="section-header"><td colspan="2">Assets</td></tr>
            @foreach ($data['assets'] as $row)
                <tr>
                    <td class="{{ $row['account']->parent_id ?? false ? 'indent' : '' }}">
                        @if (isset($row['account']->code) && $row['account']->code){{ $row['account']->code }} - @endif{{ $row['account']->name }}
                    </td>
                    <td class="text-right">{{ number_format($row['balance'], 2) }}</td>
                </tr>
            @endforeach
            <tr class="subtotal"><td>Total Assets</td><td class="text-right">{{ number_format($data['total_assets'], 2) }}</td></tr>

            <tr class="section-header"><td colspan="2">Liabilities</td></tr>
            @foreach ($data['liabilities'] as $row)
                <tr><td class="{{ $row['account']->parent_id ?? false ? 'indent' : '' }}">@if (isset($row['account']->code) && $row['account']->code){{ $row['account']->code }} - @endif{{ $row['account']->name }}</td><td class="text-right">{{ number_format($row['balance'], 2) }}</td></tr>
            @endforeach
            <tr class="subtotal"><td>Total Liabilities</td><td class="text-right">{{ number_format($data['total_liabilities'], 2) }}</td></tr>

            <tr class="section-header"><td colspan="2">Equity</td></tr>
            @foreach ($data['equity'] as $row)
                <tr><td class="{{ $row['account']->parent_id ?? false ? 'indent' : '' }}">@if (isset($row['account']->code) && $row['account']->code){{ $row['account']->code }} - @endif{{ $row['account']->name }}</td><td class="text-right">{{ number_format($row['balance'], 2) }}</td></tr>
            @endforeach
            <tr class="subtotal"><td>Total Equity</td><td class="text-right">{{ number_format($data['total_equity'], 2) }}</td></tr>
        </tbody>
        <tfoot>
            <tr class="grand-total"><td>Total Liabilities & Equity</td><td class="text-right">{{ number_format($data['total_liabilities'] + $data['total_equity'], 2) }}</td></tr>
        </tfoot>
    </table>
</body>
</html>

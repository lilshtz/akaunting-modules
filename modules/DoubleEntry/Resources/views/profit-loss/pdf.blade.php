<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Profit & Loss</title>
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
        .grand-total { background: #ddd; font-weight: bold; font-size: 11px; }
        .indent { padding-left: 20px; }
    </style>
</head>
<body>
    <h1>Profit & Loss Statement</h1>
    <div class="meta">Period: {{ $dateFrom }} to {{ $dateTo }} | Basis: {{ ucfirst($basis) }}</div>

    <table>
        <thead>
            <tr>
                <th>Account</th>
                <th class="text-right">Amount</th>
                <th class="text-right">% of Income</th>
            </tr>
        </thead>
        <tbody>
            <tr class="section-header"><td colspan="3">Income</td></tr>
            @foreach ($data['income'] as $row)
                <tr>
                    <td class="indent">{{ $row['account']->code }} - {{ $row['account']->name }}</td>
                    <td class="text-right">{{ number_format($row['balance'], 2) }}</td>
                    <td class="text-right">{{ $data['total_income'] > 0 ? number_format(($row['balance'] / $data['total_income']) * 100, 1) : '0' }}%</td>
                </tr>
            @endforeach
            <tr class="subtotal">
                <td>Total Income</td>
                <td class="text-right">{{ number_format($data['total_income'], 2) }}</td>
                <td class="text-right">100%</td>
            </tr>

            <tr class="section-header"><td colspan="3">Expenses</td></tr>
            @foreach ($data['expenses'] as $row)
                <tr>
                    <td class="indent">{{ $row['account']->code }} - {{ $row['account']->name }}</td>
                    <td class="text-right">{{ number_format($row['balance'], 2) }}</td>
                    <td class="text-right">{{ $data['total_income'] > 0 ? number_format(($row['balance'] / $data['total_income']) * 100, 1) : '0' }}%</td>
                </tr>
            @endforeach
            <tr class="subtotal">
                <td>Total Expenses</td>
                <td class="text-right">{{ number_format($data['total_expenses'], 2) }}</td>
                <td class="text-right">{{ $data['total_income'] > 0 ? number_format(($data['total_expenses'] / $data['total_income']) * 100, 1) : '0' }}%</td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="grand-total">
                <td>{{ $data['net_income'] >= 0 ? 'Net Profit' : 'Net Loss' }}</td>
                <td class="text-right">{{ number_format($data['net_income'], 2) }}</td>
                <td class="text-right">{{ $data['total_income'] > 0 ? number_format(($data['net_income'] / $data['total_income']) * 100, 1) : '0' }}%</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>

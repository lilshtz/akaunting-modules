<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Trial Balance</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; }
        h1 { font-size: 16px; margin-bottom: 5px; }
        .meta { color: #666; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 4px 8px; text-align: left; }
        th { background: #f5f5f5; font-weight: bold; }
        .text-right { text-align: right; }
        .type-header { background: #e8e8e8; font-weight: bold; }
        .subtotal { background: #f0f0f0; font-weight: bold; }
        .grand-total { background: #ddd; font-weight: bold; font-size: 11px; }
    </style>
</head>
<body>
    <h1>Trial Balance</h1>
    <div class="meta">Period: {{ $dateFrom }} to {{ $dateTo }} | Basis: {{ ucfirst($basis) }}</div>

    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Account Name</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Credit</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($types as $type)
                @if (isset($trialBalance['accounts'][$type]))
                    <tr class="type-header">
                        <td colspan="4">{{ ucfirst($type) }}</td>
                    </tr>
                    @foreach ($trialBalance['accounts'][$type] as $row)
                        <tr>
                            <td>{{ $row['account']->code }}</td>
                            <td>{{ $row['account']->name }}</td>
                            <td class="text-right">{{ $row['debit'] > 0 ? number_format($row['debit'], 2) : '' }}</td>
                            <td class="text-right">{{ $row['credit'] > 0 ? number_format($row['credit'], 2) : '' }}</td>
                        </tr>
                    @endforeach
                @endif
            @endforeach
        </tbody>
        <tfoot>
            <tr class="grand-total">
                <td colspan="2" class="text-right">Grand Total</td>
                <td class="text-right">{{ number_format($trialBalance['grand_debit'], 2) }}</td>
                <td class="text-right">{{ number_format($trialBalance['grand_credit'], 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>

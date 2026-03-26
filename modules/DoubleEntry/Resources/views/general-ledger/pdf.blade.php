<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>General Ledger</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; }
        h1 { font-size: 16px; margin-bottom: 5px; }
        .meta { color: #666; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ddd; padding: 4px 8px; text-align: left; }
        th { background: #f5f5f5; font-weight: bold; }
        .text-right { text-align: right; }
        .account-header { background: #e8e8e8; font-weight: bold; padding: 6px 8px; }
        .total-row { background: #f5f5f5; font-weight: bold; }
    </style>
</head>
<body>
    <h1>General Ledger</h1>
    <div class="meta">Period: {{ $dateFrom }} to {{ $dateTo }} | Basis: {{ ucfirst($basis) }}</div>

    @foreach ($ledgerData as $accountData)
        <div class="account-header">{{ $accountData['account']->display_name }}</div>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Reference</th>
                    <th>Description</th>
                    <th class="text-right">Debit</th>
                    <th class="text-right">Credit</th>
                    <th class="text-right">Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr class="total-row">
                    <td colspan="5">Opening Balance</td>
                    <td class="text-right">{{ number_format($accountData['opening_balance'], 2) }}</td>
                </tr>
                @foreach ($accountData['entries'] as $entry)
                    <tr>
                        <td>{{ $entry['date'] }}</td>
                        <td>{{ $entry['reference'] ?? '' }}</td>
                        <td>{{ $entry['description'] ?? '' }}</td>
                        <td class="text-right">{{ $entry['debit'] > 0 ? number_format($entry['debit'], 2) : '' }}</td>
                        <td class="text-right">{{ $entry['credit'] > 0 ? number_format($entry['credit'], 2) : '' }}</td>
                        <td class="text-right">{{ number_format($entry['balance'], 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="5">Closing Balance</td>
                    <td class="text-right">{{ number_format($accountData['closing_balance'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    @endforeach
</body>
</html>

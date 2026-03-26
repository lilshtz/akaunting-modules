<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ trans('budgets::general.variance_report') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #d1d5db; padding: 8px; text-align: left; }
        th { background: #f3f4f6; }
        .danger { background: #fee2e2; }
    </style>
</head>
<body>
    <h1>{{ trans('budgets::general.variance_report') }}</h1>
    <p>{{ $budget->name }} | {{ $budget->period_start->toDateString() }} - {{ $budget->period_end->toDateString() }}</p>
    <p>{{ trans('budgets::general.budgeted') }}: {{ number_format($report['summary']['budgeted'], 2) }}</p>
    <p>{{ trans('budgets::general.actual') }}: {{ number_format($report['summary']['actual'], 2) }}</p>
    <p>{{ trans('budgets::general.variance') }}: {{ number_format($report['summary']['variance'], 2) }}</p>

    <table>
        <thead>
        <tr>
            <th>{{ trans('budgets::general.account') }}</th>
            <th>{{ trans('budgets::general.budgeted') }}</th>
            <th>{{ trans('budgets::general.actual') }}</th>
            <th>{{ trans('budgets::general.variance') }}</th>
            <th>{{ trans('budgets::general.variance_percentage') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach($report['lines'] as $row)
            <tr class="{{ $row['is_over_budget'] ? 'danger' : '' }}">
                <td>{{ $row['account']->display_name }}</td>
                <td>{{ number_format($row['budget_amount'], 2) }}</td>
                <td>{{ number_format($row['actual_amount'], 2) }}</td>
                <td>{{ number_format($row['variance'], 2) }}</td>
                <td>{{ $row['variance_percentage'] !== null ? number_format($row['variance_percentage'], 2) . '%' : '-' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>

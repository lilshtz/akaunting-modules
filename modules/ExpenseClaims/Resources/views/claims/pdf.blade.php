<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $claim->claim_number ?: $claim->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align: top; }
        img { max-width: 180px; max-height: 180px; }
    </style>
</head>
<body>
    <h1>{{ trans('expense-claims::general.claim') }} {{ $claim->claim_number ?: $claim->id }}</h1>
    <p>{{ trans('expense-claims::general.employee') }}: {{ $claim->employee_name }}</p>
    <p>{{ trans('expense-claims::general.status') }}: {{ $claim->status_label }}</p>
    <p>{{ trans('expense-claims::general.total') }}: {{ money($claim->total, setting('default.currency', 'USD')) }}</p>

    <table>
        <thead>
        <tr>
            <th>{{ trans('expense-claims::general.item_date') }}</th>
            <th>{{ trans('expense-claims::general.category') }}</th>
            <th>{{ trans('expense-claims::general.description') }}</th>
            <th>{{ trans('expense-claims::general.item_amount') }}</th>
            <th>{{ trans('expense-claims::general.receipt') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach($claim->items as $item)
            <tr>
                <td>{{ optional($item->date)->toDateString() }}</td>
                <td>{{ $item->category?->name }}</td>
                <td>{{ $item->description }}</td>
                <td>{{ money($item->amount, setting('default.currency', 'USD')) }}</td>
                <td>
                    @if($item->receipt_path && \Illuminate\Support\Str::endsWith(strtolower($item->receipt_path), ['.jpg', '.jpeg', '.png']))
                        <img src="{{ public_path('storage/' . $item->receipt_path) }}" alt="">
                    @else
                        {{ $item->receipt_path }}
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>

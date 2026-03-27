<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $report['report_type_label'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        h1 { margin-bottom: 4px; }
        .meta { margin-bottom: 18px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; }
        th { background: #f5f5f5; text-align: left; }
        .summary td:first-child { width: 70%; }
    </style>
</head>
<body>
    <h1>{{ $report['report_type_label'] }}</h1>
    <div class="meta">
        {{ trans('auto-schedule-reports::general.report_period') }}: {{ $report['date_label'] }}<br>
        {{ trans('auto-schedule-reports::general.generated_at') }}: {{ $report['generated_at']->toDayDateTimeString() }}
    </div>

    <table>
        <thead>
        <tr>
            @foreach($report['headings'] as $heading)
                <th>{{ $heading }}</th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        @forelse($report['rows'] as $row)
            <tr>
                @foreach($row as $value)
                    <td>{{ is_numeric($value) ? number_format((float) $value, 2) : $value }}</td>
                @endforeach
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($report['headings']) }}">{{ trans('auto-schedule-reports::general.no_data') }}</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    @if(! empty($report['summary']))
        <table class="summary">
            <tbody>
            @foreach($report['summary'] as $item)
                <tr>
                    <td>{{ $item['label'] }}</td>
                    <td>{{ is_numeric($item['value']) ? number_format((float) $item['value'], 2) : $item['value'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>

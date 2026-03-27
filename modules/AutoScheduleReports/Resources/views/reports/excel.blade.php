<html>
<head>
    <meta charset="UTF-8">
</head>
<body>
    <table border="1">
        <tr>
            <td colspan="{{ count($report['headings']) }}">{{ $report['report_type_label'] }}</td>
        </tr>
        <tr>
            <td colspan="{{ count($report['headings']) }}">{{ $report['date_label'] }}</td>
        </tr>
        <tr>
            @foreach($report['headings'] as $heading)
                <th>{{ $heading }}</th>
            @endforeach
        </tr>
        @foreach($report['rows'] as $row)
            <tr>
                @foreach($row as $value)
                    <td>{{ $value }}</td>
                @endforeach
            </tr>
        @endforeach
        @if(! empty($report['summary']))
            <tr><td colspan="{{ count($report['headings']) }}"></td></tr>
            @foreach($report['summary'] as $item)
                <tr>
                    <td>{{ $item['label'] }}</td>
                    <td>{{ $item['value'] }}</td>
                </tr>
            @endforeach
        @endif
    </table>
</body>
</html>

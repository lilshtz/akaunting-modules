<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $estimate->document_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }
        .company-info {
            float: left;
            width: 50%;
        }
        .estimate-info {
            float: right;
            width: 40%;
            text-align: right;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #7c3aed;
            margin-bottom: 5px;
        }
        .estimate-title {
            font-size: 28px;
            font-weight: bold;
            color: #7c3aed;
            margin-bottom: 10px;
        }
        .info-label {
            color: #666;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
        .addresses {
            margin-bottom: 30px;
        }
        .addresses .from {
            float: left;
            width: 45%;
        }
        .addresses .to {
            float: right;
            width: 45%;
        }
        .section-title {
            font-size: 10px;
            text-transform: uppercase;
            color: #999;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.items thead th {
            background: #f3f4f6;
            padding: 10px 12px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            color: #666;
            border-bottom: 2px solid #e5e7eb;
        }
        table.items thead th.text-right {
            text-align: right;
        }
        table.items tbody td {
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        table.items tbody td.text-right {
            text-align: right;
        }
        .item-description {
            color: #666;
            font-size: 10px;
        }
        .totals {
            float: right;
            width: 250px;
        }
        .totals .row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
        }
        .totals table {
            width: 100%;
        }
        .totals table td {
            padding: 6px 0;
        }
        .totals table td.label {
            color: #666;
        }
        .totals table td.amount {
            text-align: right;
            font-weight: bold;
        }
        .totals table tr.grand-total td {
            border-top: 2px solid #333;
            font-size: 16px;
            font-weight: bold;
            padding-top: 10px;
        }
        .totals table tr.discount td {
            color: #dc2626;
        }
        .notes {
            clear: both;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        .notes h4 {
            font-size: 10px;
            text-transform: uppercase;
            color: #999;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        .footer-text {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            color: #999;
            font-size: 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .meta-row {
            margin-bottom: 4px;
        }
    </style>
</head>
<body>
    <div class="header clearfix">
        <div class="company-info">
            @if ($estimate->company)
                <div class="company-name">{{ $estimate->company->name }}</div>
                @if ($estimate->company->address)
                    <div>{{ $estimate->company->address }}</div>
                @endif
                @if ($estimate->company->city || $estimate->company->state || $estimate->company->zip_code)
                    <div>{{ implode(', ', array_filter([$estimate->company->city, $estimate->company->state, $estimate->company->zip_code])) }}</div>
                @endif
                @if ($estimate->company->email)
                    <div>{{ $estimate->company->email }}</div>
                @endif
                @if ($estimate->company->phone)
                    <div>{{ $estimate->company->phone }}</div>
                @endif
            @endif
        </div>
        <div class="estimate-info">
            <div class="estimate-title">ESTIMATE</div>
            <div class="meta-row"><span class="info-label">Number:</span> {{ $estimate->document_number }}</div>
            <div class="meta-row"><span class="info-label">Date:</span> {{ $estimate->issued_at->format('M d, Y') }}</div>
            @if ($estimate->due_at)
                <div class="meta-row"><span class="info-label">Expires:</span> {{ $estimate->due_at->format('M d, Y') }}</div>
            @endif
        </div>
    </div>

    <div class="addresses clearfix">
        <div class="to">
            <div class="section-title">Bill To</div>
            <div><strong>{{ $estimate->contact_name }}</strong></div>
            @if ($estimate->contact_address)
                <div>{{ $estimate->contact_address }}</div>
            @endif
            @if ($estimate->contact_city || $estimate->contact_state || $estimate->contact_zip_code)
                <div>{{ implode(', ', array_filter([$estimate->contact_city, $estimate->contact_state, $estimate->contact_zip_code])) }}</div>
            @endif
            @if ($estimate->contact_email)
                <div>{{ $estimate->contact_email }}</div>
            @endif
            @if ($estimate->contact_phone)
                <div>{{ $estimate->contact_phone }}</div>
            @endif
        </div>
    </div>

    @if ($estimate->title)
        <h3 style="margin-bottom: 5px;">{{ $estimate->title }}</h3>
    @endif
    @if ($estimate->subheading)
        <p style="color: #666; margin-top: 0; margin-bottom: 20px;">{{ $estimate->subheading }}</p>
    @endif

    <table class="items">
        <thead>
            <tr>
                <th>Item</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Price</th>
                <th class="text-right">Discount</th>
                <th class="text-right">Tax</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($estimate->items as $item)
                <tr>
                    <td>
                        {{ $item->name }}
                        @if ($item->description)
                            <div class="item-description">{{ $item->description }}</div>
                        @endif
                    </td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">{{ money($item->price, $estimate->currency_code) }}</td>
                    <td class="text-right">{{ $item->discount_rate ? $item->discount_rate . '%' : '-' }}</td>
                    <td class="text-right">{{ $item->tax ? money($item->tax, $estimate->currency_code) : '-' }}</td>
                    <td class="text-right">{{ money($item->total, $estimate->currency_code) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            @foreach ($estimate->totals->sortBy('sort_order') as $total)
                <tr class="{{ $total->code === 'total' ? 'grand-total' : '' }} {{ $total->code === 'discount' ? 'discount' : '' }}">
                    <td class="label">{{ trans($total->name) }}</td>
                    <td class="amount">{{ $total->code === 'discount' ? '-' : '' }}{{ money($total->amount, $estimate->currency_code) }}</td>
                </tr>
            @endforeach
        </table>
    </div>

    @if ($estimate->notes)
        <div class="notes">
            <h4>Notes / Terms</h4>
            <p>{{ $estimate->notes }}</p>
        </div>
    @endif

    @if ($estimate->footer)
        <div class="footer-text">
            {{ $estimate->footer }}
        </div>
    @endif
</body>
</html>

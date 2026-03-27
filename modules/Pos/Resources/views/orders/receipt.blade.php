<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ $order->order_number }} {{ trans('pos::general.receipt') }}</title>
    <style>
        body { font-family: monospace; margin: 0; padding: 24px; background: #f5f5f5; }
        .receipt { width: {{ (int) $setting->receipt_width }}mm; max-width: 100%; margin: 0 auto; background: #fff; padding: 20px; box-sizing: border-box; }
        .center { text-align: center; }
        .line { border-top: 1px dashed #999; margin: 12px 0; }
        .row { display: flex; justify-content: space-between; gap: 12px; font-size: 12px; }
        .item { margin-bottom: 8px; }
        @media print {
            body { background: #fff; padding: 0; }
            .actions { display: none; }
            .receipt { width: {{ (int) $setting->receipt_width }}mm; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="actions center" style="margin-bottom: 16px;">
        <button onclick="window.print()">{{ trans('pos::general.print_receipt') }}</button>
    </div>

    <div class="receipt">
        <div class="center">
            <strong>{{ $receipt['company_name'] }}</strong><br>
            {{ trans('pos::general.receipt') }}<br>
            {{ $order->order_number }}<br>
            {{ company_date($order->created_at) }}
        </div>

        <div class="line"></div>

        @foreach ($receipt['lines'] as $line)
            <div class="item">
                <div>{{ $line['name'] }}</div>
                <div class="row">
                    <span>{{ $line['quantity'] }} x @money($line['price'], setting('default.currency', 'USD'), true)</span>
                    <span>@money($line['total'], setting('default.currency', 'USD'), true)</span>
                </div>
            </div>
        @endforeach

        <div class="line"></div>

        <div class="row"><span>{{ trans('pos::general.subtotal') }}</span><span>@money($order->subtotal, setting('default.currency', 'USD'), true)</span></div>
        <div class="row"><span>{{ trans('pos::general.discount') }}</span><span>@money($order->discount, setting('default.currency', 'USD'), true)</span></div>
        <div class="row"><span>{{ trans('pos::general.tax') }}</span><span>@money($order->tax, setting('default.currency', 'USD'), true)</span></div>
        <div class="row"><strong>{{ trans('pos::general.total') }}</strong><strong>@money($order->total, setting('default.currency', 'USD'), true)</strong></div>
        <div class="row"><span>{{ trans('pos::general.paid_amount') }}</span><span>@money($order->paid_amount, setting('default.currency', 'USD'), true)</span></div>
        <div class="row"><span>{{ trans('pos::general.change_amount') }}</span><span>@money($order->change_amount, setting('default.currency', 'USD'), true)</span></div>

        <div class="line"></div>

        <div class="center">
            {{ $order->contact_id ? $order->contact->name : trans('pos::general.walk_in_customer') }}<br>
            {{ trans('pos::general.payment_methods.' . $order->payment_method) }}
        </div>
    </div>
</body>
</html>

<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>إيصال #{{ $order->id }} — {{ $restaurant->name }}</title>
    <style>
        *{box-sizing:border-box}
        body{margin:0;padding:16px;background:#eef2f0;color:#111;font-family:"Segoe UI",Tahoma,Arial,sans-serif;font-size:13px;line-height:1.6}
        .receipt{width:80mm;max-width:100%;margin:0 auto;padding:14px 12px;background:#fff}
        h1{margin:0;font-size:17px;text-align:center}
        .center{text-align:center}.muted{color:#555}
        hr{border:0;border-top:1px dashed #777;margin:8px 0}
        table{width:100%;border-collapse:collapse}
        td,th{padding:2px 0;text-align:start;vertical-align:top}
        th{font-weight:600;border-bottom:1px solid #111}
        .num{text-align:end;white-space:nowrap;font-variant-numeric:tabular-nums}
        .total td{padding-top:6px;border-top:1px solid #111;font-size:16px;font-weight:700}
        .actions{width:80mm;max-width:100%;margin:12px auto 0;display:flex;gap:8px}
        .actions button{flex:1;padding:10px;border:0;border-radius:8px;background:{{ $restaurant->brandColor() }};color:#fff;font:inherit;font-weight:600;cursor:pointer}
        .actions button.ghost{background:#fff;color:#111;border:1px solid #bbb}
        @media print{body{padding:0;background:#fff}.actions{display:none}.receipt{width:auto}@page{margin:4mm}}
    </style>
</head>
<body>
@php($money = fn ($value) => number_format((float) $value, 2))
<div class="receipt">
    <h1>{{ $restaurant->name }}</h1>
    @if($restaurant->address)<div class="center muted">{{ $restaurant->address }}</div>@endif
    @if($restaurant->phone)<div class="center muted" dir="ltr">{{ $restaurant->phone }}</div>@endif
    <hr>
    <table>
        <tr><td>إيصال رقم</td><td class="num">#{{ $order->id }}</td></tr>
        <tr><td>التاريخ</td><td class="num" dir="ltr">{{ ($order->paid_at ?? $order->created_at)->format('Y/m/d h:i A') }}</td></tr>
        <tr><td>النوع</td><td class="num">{{ $order->order_type === 'takeaway' ? 'تيك أواي' : ($order->table_number ? 'طاولة '.$order->table_number : 'صالة') }}</td></tr>
        @if($order->customer_name && $order->customer_name !== 'عميل الكاشير')<tr><td>العميل</td><td class="num">{{ $order->customer_name }}</td></tr>@endif
        @if($order->cashier)<tr><td>الكاشير</td><td class="num">{{ $order->cashier->name }}</td></tr>@endif
    </table>
    <hr>
    <table>
        <thead><tr><th>الصنف</th><th class="num">الكمية</th><th class="num">القيمة</th></tr></thead>
        <tbody>
        @foreach($order->items as $line)
            <tr><td>{{ $line->item_name }}<br><span class="muted">{{ $money($line->unit_price) }}</span></td><td class="num">{{ $line->quantity }}</td><td class="num">{{ $money($line->line_total) }}</td></tr>
        @endforeach
        </tbody>
    </table>
    <hr>
    <table>
        <tr><td>المجموع</td><td class="num">{{ $money($order->subtotal) }}</td></tr>
        @if($order->discount > 0)<tr><td>الخصم</td><td class="num">-{{ $money($order->discount) }}</td></tr>@endif
        @if($order->service > 0)<tr><td>الخدمة</td><td class="num">{{ $money($order->service) }}</td></tr>@endif
        @if($order->tax > 0)<tr><td>الضريبة</td><td class="num">{{ $money($order->tax) }}</td></tr>@endif
        <tr class="total"><td>الإجمالي</td><td class="num">{{ $money($order->total) }} {{ $order->currency }}</td></tr>
        <tr><td>الدفع</td><td class="num">{{ $order->isPaid() ? ($paymentMethods[$order->payment_method] ?? 'مدفوع') : 'غير مدفوع' }}</td></tr>
    </table>
    @if($cashier['receipt_footer'])
        <hr>
        <div class="center">{{ $cashier['receipt_footer'] }}</div>
    @endif
</div>
<div class="actions">
    <button type="button" onclick="window.print()">طباعة</button>
    <button type="button" class="ghost" onclick="window.close()">إغلاق</button>
</div>
@if(request()->boolean('print'))
<script>window.addEventListener('load', () => window.print());</script>
@endif
</body>
</html>

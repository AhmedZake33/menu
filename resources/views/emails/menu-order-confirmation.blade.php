<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        body{font-family:Tahoma,Arial,sans-serif;background:#f6f5fa;color:#211d2c;margin:0;padding:24px}
        .card{max-width:640px;margin:auto;background:#fff;border-radius:16px;padding:24px;border:1px solid #eee}
        table{width:100%;border-collapse:collapse;margin-top:16px}
        th,td{padding:10px;border-bottom:1px solid #eee;text-align:right}
        .total{font-size:20px;font-weight:800}
    </style>
</head>
<body>
    <div class="card">
        <h1>تم استلام طلبك</h1>
        <p>مرحبًا {{ $order->customer_name }}، تم تسجيل طلبك في {{ $order->restaurant->name }}.</p>
        <p>رقم الطلب: <strong>#{{ $order->id }}</strong><br>رقم الطاولة: <strong>{{ $order->table_number }}</strong></p>

        <table>
            <thead>
                <tr>
                    <th>الصنف</th>
                    <th>الكمية</th>
                    <th>الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->item_name }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ number_format($item->line_total, 2) }} {{ $order->currency }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p class="total">الإجمالي: {{ number_format($order->total, 2) }} {{ $order->currency }}</p>

        @if($order->notes)
            <p>ملاحظاتك: {{ $order->notes }}</p>
        @endif

        <p>سيقوم فريق المطعم بمراجعة الطلب وتجهيزه.</p>
    </div>
</body>
</html>

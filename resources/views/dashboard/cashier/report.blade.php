@extends('layouts.panel')
@section('title', 'تقرير الكاشير')
@section('content')
@php($money = fn ($value) => number_format((float) $value, 2))

<div class="page-head">
    <div>
        <h1>تقرير {{ $date->isToday() ? 'اليوم' : $date->format('Y/m/d') }}</h1>
        <p>كل ما تم تحصيله في هذا اليوم، من الكاشير ومن طلبات المنيو.</p>
    </div>
    <form method="get" class="d-flex gap-2" data-no-ajax>
        <input class="form-control" type="date" name="date" value="{{ $date->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" aria-label="التاريخ">
        <button class="btn btn-outline-primary">عرض</button>
    </form>
</div>

@include('dashboard.cashier._nav')

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3"><div class="stat-card"><span>إجمالي التحصيل ({{ $restaurant->currency }})</span><strong>{{ $money($summary['total']) }}</strong></div></div>
    <div class="col-6 col-xl-3"><div class="stat-card"><span>عدد الفواتير</span><strong>{{ number_format($summary['count']) }}</strong></div></div>
    <div class="col-6 col-xl-3"><div class="stat-card"><span>مبيعات الكاشير</span><strong>{{ $money($summary['cashier_sales']) }}</strong></div></div>
    <div class="col-6 col-xl-3"><div class="stat-card"><span>طلبات المنيو المحصّلة</span><strong>{{ $money($summary['menu_orders']) }}</strong></div></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card p-4 h-100">
            <h2 class="mb-3">حسب طريقة الدفع</h2>
            @if($byMethod->isEmpty())
                <p class="text-muted mb-0">لا توجد تحصيلات في هذا اليوم.</p>
            @else
                <div class="method-bars">
                    @foreach($byMethod as $row)
                        <div class="method-bar">
                            <span>{{ $row['label'] }} <small class="text-muted">({{ $row['count'] }})</small></span>
                            <span class="track"><i style="width: {{ $summary['total'] > 0 ? round($row['total'] / $summary['total'] * 100, 1) : 0 }}%"></i></span>
                            <b>{{ $money($row['total']) }}</b>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card p-4 h-100">
            <h2 class="mb-3">تفاصيل</h2>
            <div class="pos-totals m-0">
                <div><span>خصومات ممنوحة</span><span>{{ $money($summary['discount']) }}</span></div>
                <div><span>خدمة محصّلة</span><span>{{ $money($summary['service']) }}</span></div>
                <div><span>ضريبة محصّلة</span><span>{{ $money($summary['tax']) }}</span></div>
            </div>
        </div>
    </div>
</div>

<div class="card table-responsive">
    <table class="table align-middle">
        <thead><tr><th>#</th><th>الوقت</th><th>المصدر</th><th>الأصناف</th><th>الدفع</th><th>الإجمالي</th><th></th></tr></thead>
        <tbody>
        @forelse($orders as $order)
            <tr>
                <td>#{{ $order->id }}</td>
                <td>{{ $order->paid_at->format('h:i A') }}</td>
                <td>
                    {{ $order->source === 'cashier' ? 'كاشير' : 'منيو' }}
                    <small class="d-block text-muted">{{ $order->order_type === 'takeaway' ? 'تيك أواي' : ($order->table_number ? 'طاولة '.$order->table_number : 'صالة') }}</small>
                </td>
                <td>@foreach($order->items as $line)<span class="d-block">{{ $line->quantity }}× {{ $line->item_name }}</span>@endforeach</td>
                <td>{{ $paymentMethods[$order->payment_method] ?? '—' }}</td>
                <td><strong>{{ $money($order->total) }}</strong></td>
                <td class="text-end"><a class="btn btn-sm btn-outline-secondary" target="_blank" href="{{ route('dashboard.cashier.receipt', $order) }}"><i class="bi bi-printer"></i> إيصال</a></td>
            </tr>
        @empty
            <tr><td colspan="7" class="empty-state">لا توجد فواتير محصّلة في هذا اليوم.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection

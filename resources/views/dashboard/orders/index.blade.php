@extends('layouts.panel')

@section('title', 'طلبات العملاء')

@section('content')
@php($statuses = ['pending' => 'جديد', 'confirmed' => 'مؤكد', 'preparing' => 'قيد التحضير', 'completed' => 'مكتمل', 'cancelled' => 'ملغي'])
<div class="page-head">
    <div>
        <h1>طلبات العملاء</h1>
        <p>الطلبات التي سجلها العملاء من المنيو الإلكتروني.</p>
    </div>
</div>

<div class="card table-responsive">
    <table class="table align-middle mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>العميل</th>
                <th>الطاولة</th>
                <th>الأصناف</th>
                <th>الإجمالي</th>
                <th>الحالة</th>
                <th>الدفع</th>
                <th>الإيميل</th>
                <th>التاريخ</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                <tr>
                    <td>#{{ $order->id }}@if($order->source === 'cashier')<small class="d-block text-muted">كاشير</small>@endif</td>
                    <td>
                        <strong>{{ $order->customer_name }}</strong>
                        <small class="d-block text-muted">{{ $order->customer_email }}</small>
                        @if($order->customer_phone)<small class="d-block text-muted">{{ $order->customer_phone }}</small>@endif
                    </td>
                    <td>{{ $order->table_number ?? '—' }}</td>
                    <td>
                        @foreach($order->items as $item)
                            <span class="d-block">{{ $item->quantity }}× {{ $item->item_name }}</span>
                        @endforeach
                        @if($order->notes)<small class="d-block text-muted">ملاحظات: {{ $order->notes }}</small>@endif
                    </td>
                    <td>{{ number_format($order->total, 2) }} {{ $order->currency }}</td>
                    <td><span class="badge bg-secondary">{{ $statuses[$order->status] ?? $order->status }}</span></td>
                    <td>
                        @if($order->isPaid())
                            <span class="badge bg-success">مدفوع</span>
                            <small class="d-block text-muted">{{ config('modules.payment_methods.'.$order->payment_method, '') }}</small>
                        @else
                            <span class="badge bg-warning">غير مدفوع</span>
                        @endif
                    </td>
                    <td>
                        @if($order->source === 'cashier')
                            <span class="text-muted">—</span>
                        @elseif($order->email_sent_at)
                            <span class="badge bg-success">تم الإرسال</span>
                        @else
                            <span class="badge bg-warning text-dark">لم يرسل</span>
                        @endif
                    </td>
                    <td>{{ $order->created_at->format('Y/m/d h:i A') }}</td>
                    <td>
                        <form method="post" action="{{ route('dashboard.orders.update', $order) }}">
                            @csrf
                            @method('patch')
                            <select class="form-select form-select-sm mb-2" name="status">
                                @foreach($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected($order->status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <button class="btn btn-sm btn-outline-primary w-100">حفظ</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" class="empty-state">لا توجد طلبات حتى الآن.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="pagination-footer mt-3">
    {{ $orders->links() }}
</div>
@endsection

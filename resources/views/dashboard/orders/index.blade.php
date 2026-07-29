@extends('layouts.panel')

@section('title', 'طلبات العملاء')

@section('content')
<div class="settings-heading">
    <div>
        <span class="eyebrow text-primary">طلبات المنيو</span>
        <h1>طلبات العملاء</h1>
        <p class="text-muted">الطلبات التي سجلها العملاء من المنيو الإلكتروني.</p>
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
                <th>الإيميل</th>
                <th>التاريخ</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                <tr>
                    <td>#{{ $order->id }}</td>
                    <td>
                        <strong>{{ $order->customer_name }}</strong>
                        <small class="d-block text-muted">{{ $order->customer_email }}</small>
                        @if($order->customer_phone)<small class="d-block text-muted">{{ $order->customer_phone }}</small>@endif
                    </td>
                    <td>{{ $order->table_number }}</td>
                    <td>
                        @foreach($order->items as $item)
                            <span class="d-block">{{ $item->quantity }}× {{ $item->item_name }}</span>
                        @endforeach
                        @if($order->notes)<small class="d-block text-muted">ملاحظات: {{ $order->notes }}</small>@endif
                    </td>
                    <td>{{ number_format($order->total, 2) }} {{ $order->currency }}</td>
                    <td><span class="badge text-bg-light">{{ $order->status }}</span></td>
                    <td>
                        @if($order->email_sent_at)
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
                                @foreach(['pending' => 'جديد', 'confirmed' => 'مؤكد', 'preparing' => 'قيد التحضير', 'completed' => 'مكتمل', 'cancelled' => 'ملغي'] as $value => $label)
                                    <option value="{{ $value }}" @selected($order->status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <button class="btn btn-sm btn-outline-primary w-100">حفظ</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="empty-state">لا توجد طلبات حتى الآن.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="pagination-footer mt-3">
    {{ $orders->links() }}
</div>
@endsection

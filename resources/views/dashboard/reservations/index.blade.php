@extends('layouts.panel')
@section('title', 'الحجوزات')
@section('content')
@php($badges = ['pending' => 'bg-warning', 'confirmed' => 'bg-success', 'completed' => 'bg-primary', 'cancelled' => 'bg-secondary'])

<div class="page-head">
    <div>
        <h1>الحجوزات</h1>
        <p>طلبات حجز الطاولات التي أرسلها العملاء من المنيو.@if($pendingCount) عندك {{ $pendingCount }} حجز في انتظار التأكيد.@endif</p>
    </div>
</div>

<nav class="subnav" aria-label="تصفية الحجوزات">
    <a class="{{ $past ? '' : 'active' }}" href="{{ route('dashboard.reservations.index') }}">القادمة</a>
    <a class="{{ $past ? 'active' : '' }}" href="{{ route('dashboard.reservations.index', ['show' => 'past']) }}">السابقة</a>
</nav>

<div class="card table-responsive">
    <table class="table align-middle">
        <thead><tr><th>الموعد</th><th>العميل</th><th>الأفراد</th><th>ملاحظات</th><th>الحالة</th><th></th></tr></thead>
        <tbody>
        @forelse($reservations as $reservation)
            <tr>
                <td>
                    <strong>{{ $reservation->reserved_at->format('Y/m/d') }}</strong>
                    <small class="d-block text-muted">{{ $reservation->reserved_at->format('h:i A') }}@if($reservation->reserved_at->isToday()) — اليوم @endif</small>
                </td>
                <td>
                    {{ $reservation->customer_name }}
                    <a class="d-block small" dir="ltr" href="tel:{{ $reservation->customer_phone }}">{{ $reservation->customer_phone }}</a>
                </td>
                <td>{{ $reservation->guests }}</td>
                <td class="text-muted">{{ $reservation->notes ?: '—' }}</td>
                <td><span class="badge {{ $badges[$reservation->status] ?? 'bg-secondary' }}">{{ \App\Models\Reservation::STATUSES[$reservation->status] ?? $reservation->status }}</span></td>
                <td>
                    <form method="post" action="{{ route('dashboard.reservations.update', $reservation) }}" class="d-flex gap-2">
                        @csrf
                        @method('patch')
                        <select class="form-select form-select-sm" name="status" aria-label="حالة الحجز">
                            @foreach(\App\Models\Reservation::STATUSES as $value => $label)
                                <option value="{{ $value }}" @selected($reservation->status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-sm btn-outline-primary">حفظ</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty-state">{{ $past ? 'لا توجد حجوزات سابقة.' : 'لا توجد حجوزات قادمة. زر "احجز طاولة" ظاهر لعملائك في المنيو.' }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $reservations->links() }}</div>
@endsection

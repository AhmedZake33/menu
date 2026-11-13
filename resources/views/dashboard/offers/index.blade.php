@extends('layouts.panel')
@section('title', 'الخصومات والعروض')
@section('content')
<div class="page-head">
    <div><h1>الخصومات والعروض</h1><p>عروض عامة على جميع الأصناف. لخصم صنف بعينه، حدّد السعر الأصلي وسعر البيع من صفحة الأصناف.</p></div>
    <a class="btn btn-primary" href="{{ route('dashboard.offers.create') }}"><i class="bi bi-plus-lg"></i> إضافة عرض</a>
</div>
<div class="card table-responsive">
    <table class="table align-middle mb-0">
        <thead><tr><th>العرض</th><th>الخصم لكل صنف</th><th>المدة</th><th>الحالة</th><th></th></tr></thead>
        <tbody>
            @forelse($offers as $offer)
                <tr>
                    <td><strong>{{ $offer->title }}</strong><p class="text-muted mb-0 text-break">{{ $offer->description }}</p></td>
                    <td>{{ number_format($offer->discount_value, 2) }} {{ $offer->discount_type === 'percent' ? '%' : auth()->user()->restaurant->currency }}</td>
                    <td><small class="d-block">من: {{ $offer->starts_at?->format('Y/m/d h:i A') ?? 'فورًا' }}</small><small class="d-block">إلى: {{ $offer->ends_at?->format('Y/m/d h:i A') ?? 'بدون انتهاء' }}</small></td>
                    <td><span class="badge {{ $offer->statusLabel() === 'ساري' ? 'bg-success' : 'bg-secondary' }}">{{ $offer->statusLabel() }}</span></td>
                    <td><div class="d-flex gap-2">
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('dashboard.offers.edit', $offer) }}">تعديل / إيقاف</a>
                        <form method="post" action="{{ route('dashboard.offers.destroy', $offer) }}">@csrf @method('delete')<button class="btn btn-sm btn-outline-danger" onclick="return confirm('حذف العرض؟')">حذف</button></form>
                    </div></td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty-state">لا توجد عروض. أضف عرضًا جديدًا لتفعيل خصم عام.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $offers->links() }}</div>
@endsection

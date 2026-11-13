@extends('layouts.panel')
@section('title', 'العملاء')
@section('content')
<div class="page-head">
    <div>
        <h1>المطاعم</h1>
        <p>كل عميل له أقسامه وإعداداته الخاصة.</p>
    </div>
    <a class="btn btn-primary" href="{{ route('admin.restaurants.create') }}"><i class="bi bi-plus-lg"></i> إضافة مطعم</a>
</div>

<form class="mb-3"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="ابحث باسم المطعم..."></form>

<div class="card table-responsive">
    <table class="table align-middle">
        <thead><tr><th>المطعم</th><th>الاشتراك</th><th>الأقسام المفعّلة</th><th>الصفحات</th><th>الأصناف</th><th>المشاهدات</th><th></th></tr></thead>
        <tbody>
        @forelse($restaurants as $r)
            <tr>
                <td><strong>{{ $r->name }}</strong><small class="d-block text-muted" dir="ltr">/{{ $r->slug }}</small></td>
                <td>
                    @if($r->isExpired())
                        <span class="badge bg-danger">منتهي</span><small class="d-block text-danger">{{ $r->expires_at->format('Y/m/d h:i A') }}</small>
                    @elseif(! $r->is_active)
                        <span class="badge bg-secondary">معطل</span>
                    @elseif($r->expires_at)
                        <span class="badge bg-success">ساري</span><small class="d-block text-muted">حتى {{ $r->expires_at->format('Y/m/d') }}</small>
                    @else
                        <span class="badge bg-primary">بدون انتهاء</span>
                    @endif
                </td>
                <td>
                    <div class="module-dots">
                        @foreach($r->moduleStates() as $key => $on)
                            <i class="bi {{ config("modules.list.$key.icon") }} {{ $on ? '' : 'off' }}" title="{{ config("modules.list.$key.label") }}: {{ $on ? 'مفعّل' : 'موقوف' }}"></i>
                        @endforeach
                    </div>
                </td>
                <td>{{ $r->menu_pages_count }}</td>
                <td>{{ $r->items_count }}</td>
                <td>{{ $r->menu_views_count }}</td>
                <td class="text-end">
                    <div class="d-flex gap-2 justify-content-end">
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.restaurants.show', $r) }}"><i class="bi bi-eye"></i> التفاصيل</a>
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.restaurants.edit', $r) }}"><i class="bi bi-sliders"></i> الإعدادات</a>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="empty-state">لا توجد مطاعم. أضف أول عميل.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $restaurants->links() }}</div>
@endsection

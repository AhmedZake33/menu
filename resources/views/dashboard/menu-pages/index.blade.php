@extends('layouts.panel')

@section('content')
@php
    $restaurant = auth()->user()->restaurant;
    $restaurantUrl = route('public.restaurant', $restaurant);
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div>
        <span class="eyebrow text-primary">الروابط والطباعة</span>
        <h1 class="mb-0">صفحات المنيو</h1>
    </div>
    <a class="btn btn-primary" href="{{ route('dashboard.menu-pages.create') }}">
        <i class="bi bi-plus-lg"></i> صفحة جديدة
    </a>
</div>

<div class="card full-menu-qr-card p-3 p-lg-4 mb-4">
    <div class="row g-4 align-items-center">
        <div class="col-lg-8">
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="qr-icon"><i class="bi bi-qr-code"></i></span>
                <div>
                    <h2 class="h4 mb-1">QR واحد لكل المنيو</h2>
                    <p class="text-muted mb-0">استخدم الكود ده على الترابيزات أو الطباعة، وهيفتح لينك المنيو الرئيسي بكل صفحات المطعم.</p>
                </div>
            </div>
            <div class="input-group" dir="ltr">
                <input class="form-control" readonly value="{{ $restaurantUrl }}">
                <a class="btn btn-outline-secondary" target="_blank" href="{{ $restaurantUrl }}">
                    <i class="bi bi-eye"></i>
                </a>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="qr-download-box">
                <img src="{{ route('dashboard.restaurant.qr', 'svg') }}" alt="QR {{ $restaurant->name }}">
                <div class="d-grid gap-2">
                    <a class="btn btn-dark" target="_blank" href="{{ route('dashboard.restaurant.qr', 'svg') }}">
                        <i class="bi bi-filetype-svg"></i> تحميل SVG
                    </a>
                    <a class="btn btn-outline-dark" target="_blank" href="{{ route('dashboard.restaurant.qr', 'png') }}">
                        <i class="bi bi-filetype-png"></i> تحميل PNG
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    @forelse($pages as $page)
        @php($publicUrl = $page->is_default ? $restaurantUrl : route('public.menu', [$restaurant, $page]))
        <div class="col-md-6 col-xl-4">
            <div class="card h-100 p-3">
                <div class="d-flex justify-content-between gap-3">
                    <h3>{{ $page->name }}</h3>
                    @if($page->is_default)
                        <span class="badge bg-primary">افتراضية</span>
                    @endif
                </div>
                <p class="text-muted">{{ $page->categories_count }} تصنيف · {{ $page->items_count }} صنف · {{ $page->views_count }} مشاهدة</p>
                <code class="small">{{ $publicUrl }}</code>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <a class="btn btn-sm btn-primary" href="{{ route('dashboard.menu-pages.edit', $page) }}">تعديل</a>
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('dashboard.theme.edit', $page) }}">التصميم</a>
                    <a class="btn btn-sm btn-outline-dark" href="{{ route('dashboard.qr', [$page, 'svg']) }}" target="_blank">QR الصفحة</a>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ $publicUrl }}" target="_blank">معاينة</a>
                </div>
            </div>
        </div>
    @empty
        <div class="empty-state">ابدأ بإنشاء أول صفحة منيو.</div>
    @endforelse
</div>
@endsection

@extends('layouts.panel')
@section('title','لوحة المطعم')
@section('content')
@php
    $imageMenu = $restaurant->usesImageMenu();
    $shortcuts = array_filter([
        'image_menu' => $imageMenu ? ['dashboard.menu-images.index', 'رفع صور المنيو'] : ['dashboard.menu-mode.edit', 'نوع المنيو'],
        'cashier' => ['dashboard.cashier.index', 'فتح الكاشير'],
        'menu_pages' => ['dashboard.menu-pages.create', 'إنشاء صفحة منيو'],
        'categories' => ['dashboard.categories.index', 'إدارة التصنيفات'],
        'items' => ['dashboard.items.index', 'إدارة الأصناف'],
        'orders' => ['dashboard.orders.index', 'طلبات العملاء'],
        'reservations' => ['dashboard.reservations.index', 'الحجوزات'],
    ], fn ($module) => $restaurant->hasModule($module), ARRAY_FILTER_USE_KEY);
@endphp
<div class="page-head"><div><h1>مرحبًا بك في {{ $restaurant->name }}</h1><p>أدر صفحاتك ومحتواك وتابع المشاهدات من مكان واحد.</p></div></div>
<div class="row g-3 mb-4">@foreach($stats as $label=>$value)<div class="col-6 col-xl-3"><div class="stat-card"><span>{{ $label }}</span><strong>{{ number_format($value) }}</strong></div></div>@endforeach</div>
@if($shortcuts)
<div class="card p-4">
    <h3 class="mb-3">ابدأ بسرعة</h3>
    <div class="d-flex flex-wrap gap-2">
        @foreach($shortcuts as $module => [$routeName, $label])
            <a class="btn {{ $loop->first ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route($routeName) }}"><i class="bi {{ config("modules.list.$module.icon") }}"></i> {{ $label }}</a>
        @endforeach
    </div>
</div>
@endif
@endsection

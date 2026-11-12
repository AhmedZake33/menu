<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title', 'Digital Menu SaaS')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-brand-style :color="auth()->user()->restaurant?->brandColor()" />
</head>
@php
    $user = auth()->user();
    $isSuperAdmin = $user->isSuperAdmin();
    $tenant = $isSuperAdmin ? null : $user->restaurant;
    $homeUrl = $isSuperAdmin ? route('admin.dashboard') : route('dashboard.home');

    // Sidebar entries for a client: [module, route, active patterns, label].
    // An entry is rendered only when the client has that module.
    $tenantNav = [
        'المنيو' => [
            ['menu_pages', 'dashboard.menu-pages.index', ['dashboard.menu-pages.*', 'dashboard.theme.*', 'dashboard.qr'], 'صفحات المنيو'],
            ['categories', 'dashboard.categories.index', ['dashboard.categories.*'], 'التصنيفات'],
            ['items', 'dashboard.items.index', ['dashboard.items.*'], 'الأصناف'],
        ],
        'المبيعات' => [
            ['cashier', 'dashboard.cashier.index', ['dashboard.cashier.*'], 'الكاشير'],
            ['orders', 'dashboard.orders.index', ['dashboard.orders.*'], 'طلبات العملاء'],
            ['reservations', 'dashboard.reservations.index', ['dashboard.reservations.*'], 'الحجوزات'],
        ],
        'الإعدادات' => [
            ['restaurant_settings', 'dashboard.restaurant-settings.edit', ['dashboard.restaurant-settings.*'], 'بيانات المطعم'],
        ],
    ];
@endphp
<body @if($tenant) data-restaurant-channel="{{ $user->restaurant_id }}" @endif>

<div class="app-shell">
    <aside id="panelSidebar" class="offcanvas-lg offcanvas-end panel-sidebar" tabindex="-1" aria-labelledby="panelSidebarLabel">
        <div class="offcanvas-header d-lg-none">
            <h5 class="offcanvas-title" id="panelSidebarLabel">القائمة</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#panelSidebar" aria-label="إغلاق"></button>
        </div>
        <div class="panel-sidebar-inner">
            <a class="panel-brand" href="{{ $homeUrl }}">
                <span class="panel-brand-mark"><i class="bi bi-qr-code"></i></span>
                <span>
                    <strong>Digital Menu</strong>
                    <small>{{ $tenant?->name ?? 'إدارة المنصة' }}</small>
                </span>
            </a>
            <nav class="panel-sidebar-nav">
                @if($isSuperAdmin)
                    <a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2"></i> لوحة الإحصائيات</a>
                    <a class="{{ request()->routeIs('admin.restaurants.*') ? 'active' : '' }}" href="{{ route('admin.restaurants.index') }}"><i class="bi bi-shop"></i> العملاء (المطاعم)</a>
                @else
                    <a class="{{ request()->routeIs('dashboard.home') ? 'active' : '' }}" href="{{ route('dashboard.home') }}"><i class="bi bi-house"></i> الرئيسية</a>
                    @foreach($tenantNav as $groupTitle => $entries)
                        @php($visible = array_filter($entries, fn ($entry) => $tenant->hasModule($entry[0])))
                        @continue(! $visible)
                        <div class="panel-nav-title">{{ $groupTitle }}</div>
                        @foreach($visible as [$module, $routeName, $patterns, $label])
                            <a class="{{ request()->routeIs(...$patterns) ? 'active' : '' }}" href="{{ route($routeName) }}"><i class="bi {{ config("modules.list.$module.icon") }}"></i> {{ $label }}</a>
                        @endforeach
                    @endforeach
                @endif
            </nav>
            @if($tenant?->hasModule('public_menu'))
                <div class="panel-sidebar-foot">
                    <a class="btn btn-outline-primary w-100" href="{{ route('public.restaurant', $tenant) }}" target="_blank"><i class="bi bi-eye"></i> عرض المنيو</a>
                </div>
            @endif
        </div>
    </aside>

    <div class="panel-main">
        <header class="panel-topbar">
            <div class="d-flex align-items-center gap-2">
                <button class="panel-menu-toggle" type="button" data-bs-toggle="offcanvas" data-bs-target="#panelSidebar" aria-controls="panelSidebar" aria-label="فتح القائمة">
                    <i class="bi bi-list"></i>
                </button>
                <a class="panel-topbar-brand" href="{{ $homeUrl }}">Digital Menu</a>
            </div>
            <div class="panel-user">
                <span class="panel-user-avatar" aria-hidden="true">{{ mb_substr($user->name, 0, 1) }}</span>
                <span class="panel-user-name">{{ $user->name }}</span>
                <form method="post" action="{{ route('logout') }}" data-no-ajax>
                    @csrf
                    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-box-arrow-left"></i> خروج</button>
                </form>
            </div>
        </header>

        <main id="panelContent" class="panel-content-shell" data-panel-content>
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
            <x-system-copyright class="panel-copyright" />
        </main>
    </div>
</div>
@if($tenant?->hasModule('orders'))
<div class="order-live-toast" data-order-live-toast hidden>
    <div class="order-live-toast-icon"><i class="bi bi-bell-fill"></i></div>
    <div>
        <strong>طلب جديد</strong>
        <p data-order-live-message>تم استلام طلب جديد.</p>
        <a href="{{ route('dashboard.orders.index') }}">عرض الطلبات</a>
    </div>
</div>
@endif
</body>
</html>

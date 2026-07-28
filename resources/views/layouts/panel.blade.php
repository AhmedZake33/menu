<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title', 'Digital Menu SaaS')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php($isSuperAdmin = auth()->user()->isSuperAdmin())

<nav class="navbar navbar-dark bg-dark sticky-top">
    <div class="container-fluid">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-outline-light d-lg-none panel-menu-toggle" type="button" data-bs-toggle="offcanvas" data-bs-target="#panelSidebar" aria-controls="panelSidebar" aria-label="فتح القائمة">
                <i class="bi bi-list"></i>
            </button>
            <a class="navbar-brand fw-bold" href="{{ $isSuperAdmin ? route('admin.dashboard') : route('dashboard.home') }}">Digital Menu</a>
        </div>
        <div class="d-flex align-items-center gap-3 text-white">
            <span class="panel-user-name">{{ auth()->user()->name }}</span>
            <form method="post" action="{{ route('logout') }}" data-no-ajax>
                @csrf
                <button class="btn btn-sm btn-outline-light">خروج</button>
            </form>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <aside id="panelSidebar" class="offcanvas-lg offcanvas-end col-lg-2 panel-sidebar p-0" tabindex="-1" aria-labelledby="panelSidebarLabel">
            <div class="offcanvas-header d-lg-none">
                <h5 class="offcanvas-title" id="panelSidebarLabel">القائمة</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#panelSidebar" aria-label="إغلاق"></button>
            </div>
            <nav class="panel-sidebar-nav p-3">
                @if($isSuperAdmin)
                    <a href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2"></i> لوحة الإحصائيات</a>
                    <a href="{{ route('admin.restaurants.index') }}"><i class="bi bi-shop"></i> المطاعم</a>
                @else
                    <a href="{{ route('dashboard.home') }}"><i class="bi bi-house"></i> الرئيسية</a>
                    <a href="{{ route('dashboard.restaurant-settings.edit') }}"><i class="bi bi-building-gear"></i> بيانات المطعم</a>
                    <a href="{{ route('dashboard.menu-pages.index') }}"><i class="bi bi-menu-button-wide"></i> صفحات المنيو</a>
                    <a href="{{ route('dashboard.categories.index') }}"><i class="bi bi-tags"></i> التصنيفات</a>
                    <a href="{{ route('dashboard.items.index') }}"><i class="bi bi-card-list"></i> الأصناف</a>
                    <a href="{{ route('public.restaurant', auth()->user()->restaurant) }}" target="_blank"><i class="bi bi-eye"></i> عرض المنيو</a>
                @endif
            </nav>
        </aside>

        <main id="panelContent" class="col-lg-10 p-3 p-lg-4 panel-content-shell" data-panel-content>
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
</body>
</html>

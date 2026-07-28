@extends('layouts.panel')
@section('title','لوحة المطعم')
@section('content')
<div class="mb-4"><h1>مرحبًا بك في {{ $restaurant->name }}</h1><p class="text-muted">أدر صفحاتك ومحتواك وتابع المشاهدات من مكان واحد.</p></div>
<div class="row g-3 mb-4">@foreach($stats as $label=>$value)<div class="col-6 col-xl-3"><div class="stat-card"><span>{{ $label }}</span><strong>{{ number_format($value) }}</strong></div></div>@endforeach</div>
<div class="card p-4"><h3>ابدأ بسرعة</h3><div class="d-flex flex-wrap gap-2"><a class="btn btn-primary" href="{{ route('dashboard.menu-pages.create') }}">إنشاء صفحة منيو</a><a class="btn btn-outline-primary" href="{{ route('dashboard.categories.index') }}">إدارة التصنيفات</a><a class="btn btn-outline-primary" href="{{ route('dashboard.items.index') }}">إدارة الأصناف</a></div></div>
@endsection

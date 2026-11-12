@extends('layouts.panel')
@section('title','لوحة المدير العام')
@section('content')
<div class="page-head">
    <div><h1>نظرة عامة</h1><p>ملخص أداء منصة المنيو الإلكتروني</p></div>
    <a class="btn btn-primary" href="{{ route('admin.restaurants.create') }}"><i class="bi bi-plus-lg"></i> مطعم جديد</a>
</div>
<div class="row g-3 mb-4">@foreach($stats as $label=>$value)<div class="col-6 col-xl-3"><div class="stat-card"><span>{{ $label }}</span><strong>{{ number_format($value) }}</strong></div></div>@endforeach</div>
<div class="card">
    <div class="card-header fw-bold">أحدث المطاعم</div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>الاسم</th><th>الحالة</th><th>الأقسام المفعّلة</th><th>تاريخ الإضافة</th></tr></thead>
            <tbody>
            @foreach($restaurants as $r)
                <tr>
                    <td><a href="{{ route('admin.restaurants.edit',$r) }}">{{ $r->name }}</a></td>
                    <td><span class="badge {{ $r->is_active?'bg-success':'bg-secondary' }}">{{ $r->is_active?'نشط':'معطل' }}</span></td>
                    <td>{{ count(array_filter($r->moduleStates())) }} من {{ count(config('modules.list')) }}</td>
                    <td>{{ $r->created_at->format('Y/m/d') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

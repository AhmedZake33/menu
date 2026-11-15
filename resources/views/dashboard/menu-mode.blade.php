@extends('layouts.panel')
@section('title', 'نوع المنيو')
@section('content')
@php
    $options = [
        'digital' => [
            'icon' => 'bi-phone',
            'title' => 'المنيو الرقمي',
            'text' => 'تبني المنيو من لوحة التحكم: صفحات وتصنيفات وأصناف بأسعارها وصورها، مع تصميم وألوان وعروض، وتقدر تفعّل الطلبات والكاشير. تتعدل في أي وقت من غير إعادة طباعة.',
            'points' => ['تعديل الأسعار والأصناف في ثواني', 'بحث وتصنيفات للعميل', 'متوافق مع الطلبات والكاشير والعروض'],
        ],
        'images' => [
            'icon' => 'bi-images',
            'title' => 'منيو بالصور',
            'text' => 'عندك منيو جاهز (مصمَّم أو متصوَّر)؟ ارفع صور صفحاته وهتظهر لعملائك بنفس ترتيبك على نفس الرابط والـ QR، بدون أي إدخال أصناف.',
            'points' => ['ارفع صور صفحات المنيو الجاهزة', 'الصور تظهر للعميل على الرابط والـ QR', 'تقدر ترتّب الصور وتبدّلها في أي وقت'],
        ],
    ];
@endphp

<div class="page-head">
    <div>
        <h1>نوع المنيو</h1>
        <p>اختر الطريقة اللي عملاؤك يشوفوا بيها منيو {{ $restaurant->name }} على الرابط والـ QR.</p>
    </div>
</div>

<form method="post" action="{{ route('dashboard.menu-mode.update') }}">
    @csrf
    @method('put')

    <div class="row g-3 mb-4">
        @foreach($options as $key => $option)
            <div class="col-lg-6">
                <label class="card p-4 h-100 d-block" for="mode-{{ $key }}" style="cursor:pointer;{{ old('menu_mode', $current) === $key ? 'border:2px solid var(--bs-primary, #a01d48)' : '' }}">
                    <div class="d-flex align-items-start gap-3">
                        <input class="form-check-input mt-2" type="radio" name="menu_mode" id="mode-{{ $key }}" value="{{ $key }}" @checked(old('menu_mode', $current) === $key)>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi {{ $option['icon'] }} fs-3 text-primary"></i>
                                <h2 class="h4 mb-0">{{ $option['title'] }}</h2>
                                @if($current === $key)<span class="badge bg-success">الحالي</span>@endif
                            </div>
                            <p class="text-muted">{{ $option['text'] }}</p>
                            <ul class="mb-0 ps-3">
                                @foreach($option['points'] as $point)<li>{{ $point }}</li>@endforeach
                            </ul>
                        </div>
                    </div>
                </label>
            </div>
        @endforeach
    </div>

    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i>
        تقدر تغيّر اختيارك في أي وقت. بياناتك ما بتتحذفش: لما ترجع للمنيو الرقمي هتلاقي أصنافك زي ما هي، ولما ترجع للصور هتلاقي صورك. رابط الـ QR ثابت لا يتغير.
        @if($imagesCount)<br>لديك حاليًا {{ $imagesCount }} صورة مرفوعة.@endif
    </div>

    <div class="settings-actions">
        <button class="btn btn-primary btn-lg px-5"><i class="bi bi-check2-circle"></i> حفظ الاختيار</button>
        @if($current === 'images')
            <a class="btn btn-outline-primary btn-lg" href="{{ route('dashboard.menu-images.index') }}"><i class="bi bi-images"></i> إدارة صور المنيو</a>
        @endif
    </div>
</form>
@endsection

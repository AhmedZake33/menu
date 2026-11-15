@extends('layouts.panel')
@section('title', 'صور المنيو')
@section('content')
@php
    $restaurantUrl = route('public.restaurant', $restaurant);
    $left = $limit > 0 ? max(0, $limit - $images->count()) : null;
@endphp

<div class="page-head">
    <div>
        <span class="eyebrow text-primary">منيو بالصور</span>
        <h1>صور المنيو</h1>
        <p>ارفع صور صفحات منيوك بالترتيب اللي عايزه يظهر للعملاء.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('dashboard.menu-mode.edit') }}"><i class="bi bi-arrow-repeat"></i> تغيير نوع المنيو</a>
</div>

@if(! $restaurant->usesImageMenu())
    <div class="alert alert-warning">
        المنيو الظاهر لعملائك حاليًا هو المنيو الرقمي، والصور دي لن تظهر. <a href="{{ route('dashboard.menu-mode.edit') }}">اختر "منيو بالصور"</a> لعرضها.
    </div>
@endif

<div class="card p-3 p-lg-4 mb-4">
    <div class="row g-4 align-items-center">
        <div class="col-lg-8">
            <h2 class="h5 mb-2">رابط المنيو</h2>
            <p class="text-muted">الرابط والـ QR دول بيفتحوا صور منيوك مباشرة.</p>
            <div class="input-group" dir="ltr">
                <input class="form-control" readonly value="{{ $restaurantUrl }}">
                <a class="btn btn-outline-secondary" target="_blank" href="{{ $restaurantUrl }}"><i class="bi bi-eye"></i></a>
            </div>
        </div>
        @if($restaurant->hasModule('qr'))
            <div class="col-lg-4">
                <div class="qr-download-box">
                    <img src="{{ route('dashboard.restaurant.qr', 'svg') }}" alt="QR {{ $restaurant->name }}">
                    <div class="d-grid gap-2">
                        <a class="btn btn-primary" href="{{ route('dashboard.restaurant.qr', 'png') }}" download="{{ $restaurant->slug }}-menu-qr.png"><i class="bi bi-download"></i> تحميل QR (PNG)</a>
                        <a class="btn btn-outline-primary" href="{{ route('dashboard.restaurant.qr', 'svg') }}" download="{{ $restaurant->slug }}-menu-qr.svg">تحميل SVG للطباعة</a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<section class="card form-section">
    <h2><i class="bi bi-cloud-arrow-up"></i> رفع صور جديدة</h2>
    <p class="section-hint">
        JPG أو PNG أو WEBP، حتى 8 ميجا للصورة وحتى 20 صورة في المرة.
        @if($limit > 0) المتاح لك: {{ $left }} من {{ $limit }} صورة. @endif
    </p>
    <form method="post" action="{{ route('dashboard.menu-images.store') }}" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 align-items-start">
        @csrf
        <div class="flex-grow-1">
            <input class="form-control @error('images') is-invalid @enderror @error('images.*') is-invalid @enderror" type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required>
            @error('images')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            @error('images.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <button class="btn btn-primary" @disabled($left === 0)><i class="bi bi-upload"></i> رفع</button>
    </form>
</section>

<section class="card form-section">
    <h2><i class="bi bi-images"></i> صفحات المنيو ({{ $images->count() }})</h2>
    @if($images->isEmpty())
        <p class="text-muted mb-0">لسه ما رفعتش أي صورة. ارفع أول صفحة من منيوك.</p>
    @else
        <p class="section-hint">الصور بتظهر للعميل من الأولى للأخيرة. استخدم الأسهم لإعادة الترتيب.</p>
        <div class="row g-3">
            @foreach($images as $image)
                <div class="col-6 col-md-4 col-xl-3">
                    <div class="card h-100">
                        <a href="{{ $image->url() }}" target="_blank" rel="noopener"><img class="card-img-top" src="{{ $image->url() }}" alt="صفحة {{ $loop->iteration }}" style="aspect-ratio:3/4;object-fit:cover" loading="lazy"></a>
                        <div class="card-body p-2 d-flex align-items-center justify-content-between gap-1">
                            <span class="badge bg-secondary">{{ $loop->iteration }}</span>
                            <div class="d-flex gap-1">
                                <form method="post" action="{{ route('dashboard.menu-images.move', $image) }}">@csrf @method('patch')<input type="hidden" name="direction" value="up"><button class="btn btn-sm btn-outline-secondary" @disabled($loop->first) title="تقديم" aria-label="تقديم"><i class="bi bi-arrow-right"></i></button></form>
                                <form method="post" action="{{ route('dashboard.menu-images.move', $image) }}">@csrf @method('patch')<input type="hidden" name="direction" value="down"><button class="btn btn-sm btn-outline-secondary" @disabled($loop->last) title="تأخير" aria-label="تأخير"><i class="bi bi-arrow-left"></i></button></form>
                                <form method="post" action="{{ route('dashboard.menu-images.destroy', $image) }}">@csrf @method('delete')<button class="btn btn-sm btn-outline-danger" title="حذف" aria-label="حذف" onclick="return confirm('حذف هذه الصورة؟')"><i class="bi bi-trash"></i></button></form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>
@endsection

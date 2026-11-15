@extends('layouts.panel')
@section('title', $restaurant->exists ? 'إعدادات العميل' : 'عميل جديد')
@section('content')
@php
    $cashier = $restaurant->cashierSettings();
    $moduleOn = fn (string $key) => (bool) old("modules.$key", $restaurant->moduleEnabled($key));
@endphp

<div class="page-head">
    <div>
        <h1>{{ $restaurant->exists ? $restaurant->name : 'عميل جديد' }}</h1>
        <p>{{ $restaurant->exists ? 'كل ما يراه هذا العميل في لوحته يُحدَّد من هنا.' : 'أنشئ المطعم وحساب مديره واختر ما يظهر له.' }}</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @if($restaurant->exists)
            <a class="btn btn-outline-primary" href="{{ route('admin.restaurants.show', $restaurant) }}"><i class="bi bi-eye"></i> كل بيانات المطعم</a>
        @endif
        <a class="btn btn-outline-secondary" href="{{ route('admin.restaurants.index') }}"><i class="bi bi-arrow-right"></i> كل العملاء</a>
    </div>
</div>

<form method="post" action="{{ $restaurant->exists ? route('admin.restaurants.update', $restaurant) : route('admin.restaurants.store') }}">
    @csrf
    @if($restaurant->exists) @method('put') @endif

    <section class="card form-section">
        <h2><i class="bi bi-shop"></i> بيانات العميل</h2>
        <p class="section-hint">الاسم والرابط ومدة الاشتراك.</p>
        <div class="row g-3">
            <div class="col-md-6"><label>اسم المطعم</label><input class="form-control" name="name" required value="{{ old('name', $restaurant->name) }}"></div>
            <div class="col-md-6"><label>الرابط المختصر</label><input class="form-control" dir="ltr" name="slug" required value="{{ old('slug', $restaurant->slug) }}"></div>
            <div class="col-md-6"><label>البريد</label><input class="form-control" type="email" dir="ltr" name="email" value="{{ old('email', $restaurant->email) }}"></div>
            <div class="col-md-6">
                <label>تاريخ ووقت انتهاء الاشتراك</label>
                <input class="form-control" type="datetime-local" name="expires_at" value="{{ old('expires_at', $restaurant->expires_at?->format('Y-m-d\TH:i')) }}">
                <small class="text-muted">اتركه فارغًا لاشتراك بدون انتهاء.</small>
            </div>
            @unless($restaurant->exists)
                <div class="col-md-4"><label>اسم المدير</label><input class="form-control" name="admin_name" required value="{{ old('admin_name') }}"></div>
                <div class="col-md-4"><label>بريد المدير</label><input class="form-control" type="email" dir="ltr" name="admin_email" required value="{{ old('admin_email') }}"></div>
                <div class="col-md-4"><label>كلمة المرور</label><input class="form-control" type="password" name="password" required></div>
            @else
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="isActive" name="is_active" value="1" @checked(old('is_active', $restaurant->is_active))>
                        <label class="form-check-label" for="isActive">المطعم نشط</label>
                    </div>
                </div>
            @endunless
        </div>
    </section>

    <section class="card form-section">
        <h2><i class="bi bi-images"></i> نوع المنيو</h2>
        <p class="section-hint">المنيو الرقمي يُبنى من لوحة العميل، أما "منيو بالصور" فيرفع العميل صور منيوه وتظهر على الرابط والـ QR. العميل نفسه يقدر يغيّره من لوحته طالما قسم "المنيو بالصور" مفعّل له.</p>
        <select class="form-select" name="menu_mode" style="max-width: 320px">
            @foreach(config('modules.menu_modes') as $mode => $modeLabel)
                <option value="{{ $mode }}" @selected(old('menu_mode', $restaurant->menu_mode ?? 'digital') === $mode)>{{ $modeLabel }}</option>
            @endforeach
        </select>
    </section>

    <section class="card form-section">
        <h2><i class="bi bi-grid-1x2"></i> الأقسام المتاحة لهذا العميل</h2>
        <p class="section-hint">القسم المُوقَف يختفي من لوحة العميل ولا يمكن فتح رابطه.</p>
        <div class="module-grid">
            @foreach(config('modules.list') as $key => $module)
                <label class="module-switch" for="module-{{ $key }}">
                    <i class="bi {{ $module['icon'] }}"></i>
                    <span class="module-text">
                        <strong>{{ $module['label'] }}</strong>
                        <small>{{ $module['description'] }}</small>
                    </span>
                    <input type="hidden" name="modules[{{ $key }}]" value="0">
                    <span class="form-check form-switch p-0 m-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="module-{{ $key }}" name="modules[{{ $key }}]" value="1" @checked($moduleOn($key))>
                    </span>
                </label>
            @endforeach
        </div>
    </section>

    <section class="card form-section">
        <h2><i class="bi bi-palette"></i> لون لوحة العميل</h2>
        <p class="section-hint">اللون الأساسي للأزرار والقائمة في لوحة هذا العميل.</p>
        @php($brand = strtolower(old('brand_color', $restaurant->brandColor())))
        <div class="color-swatches" data-color-swatches>
            @foreach(config('modules.brand.presets') as $hex => $name)
                <label title="{{ $name }}">
                    <input type="radio" name="brand_preset" value="{{ $hex }}" @checked($brand === $hex)>
                    <span style="background: {{ $hex }}"></span>
                    <small>{{ $name }}</small>
                </label>
            @endforeach
            <label class="color-custom">
                <input class="form-control form-control-color" type="color" name="brand_color" value="{{ $brand }}" aria-label="لون مخصص">
                <small>لون آخر</small>
            </label>
        </div>
    </section>

    <section class="card form-section">
        <h2><i class="bi bi-speedometer"></i> حدود الاشتراك</h2>
        <p class="section-hint">اكتب 0 لعدد غير محدود.</p>
        <div class="row g-3">
            @foreach(config('modules.limits') as $key => $label)
                <div class="col-md-4">
                    <label>{{ $label }}</label>
                    <input class="form-control" type="number" min="0" name="limits[{{ $key }}]" value="{{ old("limits.$key", $restaurant->limit($key)) }}">
                </div>
            @endforeach
        </div>
    </section>

    <section class="card form-section">
        <h2><i class="bi bi-cash-coin"></i> إعدادات الكاشير</h2>
        <p class="section-hint">تُطبَّق عند تفعيل قسم الكاشير. العميل يستطيع تعديل النسب وطرق الدفع من لوحته، أما السماح بالخصم فمن هنا فقط.</p>
        <div class="row g-3">
            <div class="col-md-3"><label>الضريبة %</label><input class="form-control" type="number" step="0.01" min="0" max="100" name="cashier[tax_percent]" value="{{ old('cashier.tax_percent', $cashier['tax_percent']) }}"></div>
            <div class="col-md-3"><label>الخدمة %</label><input class="form-control" type="number" step="0.01" min="0" max="100" name="cashier[service_percent]" value="{{ old('cashier.service_percent', $cashier['service_percent']) }}"></div>
            <div class="col-md-6"><label>سطر أسفل الإيصال</label><input class="form-control" name="cashier[receipt_footer]" maxlength="200" value="{{ old('cashier.receipt_footer', $cashier['receipt_footer']) }}"></div>
            <div class="col-md-6">
                <label class="d-block">طرق الدفع المقبولة</label>
                @foreach(config('modules.payment_methods') as $key => $label)
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="pm-{{ $key }}" name="cashier[payment_methods][]" value="{{ $key }}" @checked(in_array($key, old('cashier.payment_methods', $cashier['payment_methods'])))>
                        <label class="form-check-label" for="pm-{{ $key }}">{{ $label }}</label>
                    </div>
                @endforeach
            </div>
            <div class="col-md-6">
                <label class="d-block">الخصم</label>
                <div class="form-check form-switch">
                    <input type="hidden" name="cashier[allow_discount]" value="0">
                    <input class="form-check-input" type="checkbox" role="switch" id="allowDiscount" name="cashier[allow_discount]" value="1" @checked(old('cashier.allow_discount', $cashier['allow_discount']))>
                    <label class="form-check-label" for="allowDiscount">السماح للكاشير بإضافة خصم على الفاتورة</label>
                </div>
            </div>
        </div>
    </section>

    @if($restaurant->exists)
        <section class="card form-section">
            <h2><i class="bi bi-star"></i> QR التقييم على Google Maps</h2>
            <p class="section-hint">الصق رابط المطعم من Google Maps (مشاركة ← نسخ الرابط) والـ Place ID هيتستخرج تلقائيًا. بدونه مش هيشتغل كود التقييم.</p>
            <div class="row g-3">
                <div class="col-md-6"><label>رابط Google Maps</label><input class="form-control" type="url" dir="ltr" name="map_url" data-google-place-source value="{{ old('map_url', $restaurant->map_url) }}" placeholder="https://www.google.com/maps/place/..."></div>
                <div class="col-md-6">
                    <label>Google Place ID</label>
                    <input class="form-control" dir="ltr" name="google_place_id" data-google-place-id value="{{ old('google_place_id', $restaurant->google_place_id) }}" placeholder="ChIJ...">
                    <div class="form-text" data-google-place-status>@if($restaurant->hasGoogleReviewLink())<span class="text-success">جاهز — كود التقييم شغال.</span>@else<span class="text-danger">لسه مفيش Place ID.</span>@endif</div>
                </div>
                @if($restaurant->hasGoogleReviewLink())
                    <div class="col-12"><div class="input-group" dir="ltr"><input class="form-control" readonly value="{{ $restaurant->googleReviewUrl() }}"><span class="input-group-text">صفحة التقييم</span></div></div>
                @endif
            </div>
        </section>
    @endif

    <div class="settings-actions">
        <button class="btn btn-primary btn-lg px-5"><i class="bi bi-check2-circle"></i> {{ $restaurant->exists ? 'حفظ التغييرات' : 'إنشاء العميل' }}</button>
    </div>
</form>
@endsection

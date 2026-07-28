@extends('layouts.panel')

@section('title', 'بيانات المطعم')

@section('content')
<div class="settings-heading">
    <div>
        <span class="eyebrow text-primary">إعدادات الحساب</span>
        <h1>بيانات المطعم أو الكافيه</h1>
        <p class="text-muted">هذه البيانات تظهر للعملاء في المنيو الإلكتروني.</p>
    </div>
    <a class="btn btn-outline-primary" href="{{ route('public.restaurant', $restaurant) }}" target="_blank">
        <i class="bi bi-box-arrow-up-left"></i> معاينة المنيو
    </a>
</div>

<form method="post" enctype="multipart/form-data" action="{{ route('dashboard.restaurant-settings.update') }}">
    @csrf
    @method('put')

    <div class="card settings-card mb-4">
        <div class="card-body p-4">
            <h2 class="settings-section-title"><i class="bi bi-images"></i> الهوية البصرية</h2>
            <div class="row g-4">
                <div class="col-lg-4">
                    <label class="form-label fw-semibold">شعار المطعم</label>
                    <div class="image-upload-box image-upload-logo">
                        @if($restaurant->logo)
                            <img src="{{ Storage::url($restaurant->logo) }}" alt="الشعار">
                        @else
                            <div class="image-placeholder"><i class="bi bi-shop"></i><span>لا يوجد شعار</span></div>
                        @endif
                    </div>
                    <input class="form-control mt-2" type="file" name="logo" accept=".jpg,.jpeg,.png,.webp">
                    <small class="text-muted">بحد أقصى 2MB</small>
                </div>
                <div class="col-lg-8">
                    <label class="form-label fw-semibold">صورة الغلاف</label>
                    <div class="image-upload-box image-upload-cover">
                        @if($restaurant->cover_image)
                            <img src="{{ Storage::url($restaurant->cover_image) }}" alt="الغلاف">
                        @else
                            <div class="image-placeholder"><i class="bi bi-image"></i><span>لا توجد صورة غلاف</span></div>
                        @endif
                    </div>
                    <input class="form-control mt-2" type="file" name="cover_image" accept=".jpg,.jpeg,.png,.webp">
                    <small class="text-muted">بحد أقصى 5MB</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card settings-card mb-4">
        <div class="card-body p-4">
            <h2 class="settings-section-title"><i class="bi bi-shop-window"></i> البيانات الأساسية</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">الاسم *</label>
                    <input class="form-control" name="name" required value="{{ old('name', $restaurant->name) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">الرابط المختصر *</label>
                    <div class="input-group" dir="ltr">
                        <span class="input-group-text">/r/</span>
                        <input class="form-control" name="slug" required value="{{ old('slug', $restaurant->slug) }}">
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">نبذة عن المطعم</label>
                    <textarea class="form-control" name="description" rows="4">{{ old('description', $restaurant->description) }}</textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">الهاتف</label>
                    <input class="form-control" name="phone" value="{{ old('phone', $restaurant->phone) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">واتساب</label>
                    <input class="form-control" name="whatsapp" value="{{ old('whatsapp', $restaurant->whatsapp) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">البريد الإلكتروني</label>
                    <input class="form-control" type="email" name="email" value="{{ old('email', $restaurant->email) }}">
                </div>
                <div class="col-md-8">
                    <label class="form-label">العنوان</label>
                    <input class="form-control" name="address" value="{{ old('address', $restaurant->address) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">العملة</label>
                    <select class="form-select" name="currency">
                        @foreach(['EGP' => 'جنيه مصري', 'SAR' => 'ريال سعودي', 'AED' => 'درهم إماراتي', 'USD' => 'دولار'] as $code => $label)
                            <option value="{{ $code }}" @selected(old('currency', $restaurant->currency) === $code)>{{ $label }} ({{ $code }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card settings-card mb-4">
        <div class="card-body p-4">
            <h2 class="settings-section-title"><i class="bi bi-geo-alt"></i> موقع المطعم على الخريطة</h2>
            <div class="row g-3">
                <div class="col-lg-8">
                    <div
                        id="restaurant-location-picker"
                        class="restaurant-location-picker"
                        data-lat="{{ old('map_latitude', $restaurant->map_latitude) }}"
                        data-lng="{{ old('map_longitude', $restaurant->map_longitude) }}"
                        data-address="{{ old('address', $restaurant->address) }}"
                    ></div>
                </div>
                <div class="col-lg-4">
                    <div class="location-picker-panel">
                        <p class="text-muted">اضغط على الخريطة أو اسحب العلامة لتحديد مكان المطعم بدقة. الإحداثيات هتتخزن وتظهر للعميل في المنيو.</p>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label">Latitude</label>
                                <input id="map_latitude" class="form-control" name="map_latitude" dir="ltr" readonly value="{{ old('map_latitude', $restaurant->map_latitude) }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Longitude</label>
                                <input id="map_longitude" class="form-control" name="map_longitude" dir="ltr" readonly value="{{ old('map_longitude', $restaurant->map_longitude) }}">
                            </div>
                        </div>
                        <label class="form-label mt-3">رابط Google Maps اختياري</label>
                        <input class="form-control" type="url" dir="ltr" name="map_url" value="{{ old('map_url', $restaurant->map_url) }}" placeholder="https://maps.google.com/...">
                        <button id="clear-location-picker" class="btn btn-outline-secondary w-100 mt-3" type="button">
                            <i class="bi bi-x-circle"></i> حذف التحديد
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card settings-card mb-4">
        <div class="card-body p-4">
            <h2 class="settings-section-title"><i class="bi bi-share"></i> روابط التواصل</h2>
            <div class="row g-3">
                @foreach(['website_url' => 'الموقع الإلكتروني', 'facebook_url' => 'Facebook', 'instagram_url' => 'Instagram', 'tiktok_url' => 'TikTok'] as $field => $label)
                    <div class="col-md-6">
                        <label class="form-label">{{ $label }}</label>
                        <input class="form-control" type="url" dir="ltr" name="{{ $field }}" value="{{ old($field, $restaurant->$field) }}" placeholder="https://">
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="settings-actions">
        <button class="btn btn-primary btn-lg px-5"><i class="bi bi-check2-circle"></i> حفظ التغييرات</button>
    </div>
</form>
@endsection

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
                <div class="col-md-4">
                    <label class="form-label">عدد الطاولات</label>
                    <input class="form-control" type="number" name="tables_count" min="0" max="500" value="{{ old('tables_count', $restaurant->tables_count) }}">
                    <small class="text-muted">
                        @if($restaurant->ordering_enabled)
                            يستخدمها العملاء لاختيار رقم الطاولة عند إرسال الطلب.
                        @else
                            الطلبات غير مفعلة من الأدمن حاليًا.
                        @endif
                    </small>
                </div>
            </div>
        </div>
    </div>

    <div class="card settings-card mb-4">
        <div class="card-body p-4">
            <h2 class="settings-section-title"><i class="bi bi-geo-alt"></i> موقع المطعم على الخريطة</h2>
            <label class="form-label">رابط المطعم على Google Maps</label>
            <div class="input-group" dir="ltr">
                <input
                    class="form-control"
                    type="url"
                    name="map_url"
                    data-google-place-source
                    value="{{ old('map_url', $restaurant->map_url) }}"
                    placeholder="https://www.google.com/maps/place/..."
                >
                @if ($restaurant->map_url)
                    <a class="btn btn-outline-secondary" href="{{ $restaurant->map_url }}" target="_blank" rel="noopener" title="فتح على Google Maps">
                        <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                @endif
            </div>
            <small class="text-muted">
                افتح المطعم على Google Maps، دوس <b>مشاركة</b> و<b>نسخ الرابط</b>، والصقه هنا.
                الإحداثيات والـ Place ID (لوين التقييم) هنستخرجهم من الرابط لوحدهم.
            </small>
            <div class="form-text" data-google-place-status>
                @if ($reviewUrl)
                    <span class="text-success"><i class="bi bi-check-circle"></i> جاهز — الكود بيفتح صفحة التقييم مباشرة.</span>
                @else
                    <span class="text-danger"><i class="bi bi-exclamation-circle"></i>
                        محتاجين رابط المطعم على Google Maps الأول، من غيره مش هينفع نفتح صفحة التقييم (النجوم والتعليق).
                    </span>
                @endif
            </div>
        </div>
    </div>

    <div class="card settings-card mb-4">
        <div class="card-body p-4">
            <h2 class="settings-section-title"><i class="bi bi-star"></i> QR التقييم على Google Maps</h2>
            <p class="text-muted">
                اطبع الكود ده أو حطه على الترابيز والفواتير. لما العميل يمسح الكود بيفتح صفحة تقييم المطعم على Google Maps على طول ويقدر يضيف نجومه وتعليقه.
            </p>
            <div class="row g-4 align-items-center">
                <div class="col-lg-8">
                    <label class="form-label fw-semibold">Google Place ID</label>
                    <input
                        class="form-control"
                        dir="ltr"
                        name="google_place_id"
                        data-google-place-id
                        value="{{ old('google_place_id', $restaurant->google_place_id) }}"
                        placeholder="مثال: ChIJN1t_tDeuEmsRUsoyG83frY4"
                    >
                    <small class="text-muted">
                        افتح المطعم على Google Maps، دوس <b>مشاركة</b> و<b>نسخ الرابط</b>، والصقه في خانة
                        <b>رابط Google Maps</b> في قسم الخريطة بالأعلى — الـ Place ID هيتستخرج لوحده.
                    </small>
                    <div class="form-text" data-google-place-status>
                        @if ($reviewUrl)
                            <span class="text-success"><i class="bi bi-check-circle"></i> جاهز — الكود بيفتح صفحة التقييم مباشرة.</span>
                        @else
                            <span class="text-danger"><i class="bi bi-exclamation-circle"></i>
                                محتاجين رابط المطعم على Google Maps الأول، من غيره مش هينفع نفتح صفحة التقييم (النجوم والتعليق).
                            </span>
                        @endif
                    </div>
                </div>
                <div class="col-lg-4">
                    @if ($reviewUrl)
                        <div class="input-group mb-3" dir="ltr">
                            <input class="form-control" readonly value="{{ $reviewUrl }}">
                            <a class="btn btn-outline-secondary" target="_blank" href="{{ $reviewUrl }}" rel="noopener">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                        </div>
                        <div class="qr-download-box">
                            <img src="{{ route('dashboard.restaurant.google-review-qr', 'svg') }}" alt="QR تقييم {{ $restaurant->name }} على Google Maps">
                            <div class="d-grid gap-2">
                                <a class="btn btn-dark" target="_blank" href="{{ route('dashboard.restaurant.google-review-qr', 'svg') }}">
                                    <i class="bi bi-filetype-svg"></i> تحميل SVG
                                </a>
                                <a class="btn btn-outline-dark" target="_blank" href="{{ route('dashboard.restaurant.google-review-qr', 'png') }}">
                                    <i class="bi bi-filetype-png"></i> تحميل PNG
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="location-picker-panel text-center py-4">
                            <i class="bi bi-qr-code fs-1 text-muted"></i>
                            <p class="text-muted mb-0 mt-2">كود التقييم هيظهر هنا أول ما تحفظ رابط المطعم على Google Maps.</p>
                        </div>
                    @endif
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

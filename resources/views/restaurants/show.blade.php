@extends('layouts.panel')
@section('title', 'تفاصيل المطعم')
@section('content')
@php
    $isSuperAdmin = auth()->user()->isSuperAdmin();
    $cashier = $restaurant->cashierSettings();
    $sections = [
        'البيانات الأساسية' => [
            'رقم المطعم' => $restaurant->id,
            'اسم المطعم' => $restaurant->name,
            'الرابط المختصر' => $restaurant->slug,
            'رابط المنيو' => route('public.restaurant', $restaurant),
            'نبذة عن المطعم' => $restaurant->description,
            'العملة' => $restaurant->currency,
            'اللغة الافتراضية' => $restaurant->default_locale,
            'عدد الطاولات' => $restaurant->tables_count,
        ],
        'بيانات التواصل والموقع' => [
            'الهاتف' => $restaurant->phone,
            'واتساب' => $restaurant->whatsapp,
            'البريد الإلكتروني للمطعم' => $restaurant->email,
            'العنوان' => $restaurant->address,
            'رابط Google Maps' => $restaurant->map_url,
            'خط العرض' => $restaurant->map_latitude,
            'خط الطول' => $restaurant->map_longitude,
            'Google Place ID' => $restaurant->google_place_id,
            'الموقع الإلكتروني' => $restaurant->website_url,
            'Facebook' => $restaurant->facebook_url,
            'Instagram' => $restaurant->instagram_url,
            'TikTok' => $restaurant->tiktok_url,
        ],
        'الاشتراك والحساب' => [
            'حالة المطعم' => $restaurant->is_active ? 'نشط' : 'معطل',
            'حالة الاشتراك' => $restaurant->isExpired() ? 'منتهي' : 'ساري',
            'انتهاء الاشتراك' => $restaurant->expires_at?->format('Y/m/d h:i A') ?? 'بدون انتهاء',
            'تاريخ الإنشاء' => $restaurant->created_at?->format('Y/m/d h:i A'),
            'آخر تحديث' => $restaurant->updated_at?->format('Y/m/d h:i A'),
            'لون لوحة المطعم' => $restaurant->brandColor(),
        ],
        'إعدادات الكاشير' => [
            'الضريبة %' => $cashier['tax_percent'],
            'الخدمة %' => $cashier['service_percent'],
            'السماح بالخصم' => $cashier['allow_discount'] ? 'نعم' : 'لا',
            'طرق الدفع المقبولة' => implode('، ', $restaurant->paymentMethods()),
            'سطر أسفل الإيصال' => $cashier['receipt_footer'],
        ],
    ];
@endphp

<div class="page-head">
    <div>
        <h1>تفاصيل {{ $restaurant->name }}</h1>
        <p>بيانات المطعم وحسابات المديرين والاشتراك والإعدادات.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-primary" href="{{ $isSuperAdmin ? route('admin.restaurants.edit', $restaurant) : route('dashboard.restaurant-settings.edit') }}"><i class="bi bi-pencil"></i> تعديل البيانات</a>
        <a class="btn btn-outline-secondary" href="{{ $isSuperAdmin ? route('admin.restaurants.index') : route('dashboard.home') }}"><i class="bi bi-arrow-right"></i> رجوع</a>
    </div>
</div>

<section class="card p-4 mb-4">
    <h2 class="h5 mb-3"><i class="bi bi-person-badge"></i> بيانات مدير المطعم</h2>
    @forelse($restaurant->administrators as $administrator)
        <dl class="row g-3 mb-0 {{ $loop->first ? '' : 'border-top pt-3 mt-3' }}">
            <div class="col-md-4"><dt class="text-muted fw-normal">اسم المدير</dt><dd class="mb-0 text-break">{{ $administrator->name }}</dd></div>
            <div class="col-md-5"><dt class="text-muted fw-normal">إيميل المدير</dt><dd class="mb-0 text-break"><bdi>{{ $administrator->email }}</bdi></dd></div>
            <div class="col-md-3"><dt class="text-muted fw-normal">حالة حساب المدير</dt><dd class="mb-0">{{ $administrator->is_active ? 'نشط' : 'معطل' }}</dd></div>
        </dl>
    @empty
        <p class="text-muted mb-0">لا يوجد حساب مدير مرتبط بهذا المطعم.</p>
    @endforelse
</section>

@foreach($sections as $title => $fields)
    <section class="card p-4 mb-4">
        <h2 class="h5 mb-3">{{ $title }}</h2>
        <dl class="row g-3 mb-0">
            @foreach($fields as $label => $value)
                <div class="col-md-6">
                    <dt class="text-muted fw-normal">{{ $label }}</dt>
                    <dd class="mb-0 text-break" style="white-space: pre-line"><bdi>{{ filled($value) ? $value : 'غير مسجل' }}</bdi></dd>
                </div>
            @endforeach
        </dl>
    </section>
@endforeach

<section class="card p-4 mb-4">
    <h2 class="h5 mb-3">الشعار وصورة الغلاف</h2>
    <div class="row g-3">
        @foreach(['logo' => 'شعار المطعم', 'cover_image' => 'صورة الغلاف'] as $field => $label)
            <div class="col-md-6">
                <h3 class="h6 text-muted">{{ $label }}</h3>
                @if($url = $restaurant->fileUrl($restaurant->$field))
                    <img class="img-fluid rounded" src="{{ $url }}" alt="{{ $label }}" style="max-height: 200px">
                @else
                    <p class="text-muted mb-0">{{ $restaurant->$field ? 'الملفات والصور غير مفعلة في الاشتراك.' : 'غير مسجل' }}</p>
                @endif
            </div>
        @endforeach
    </div>
</section>

<section class="card p-4 mb-4">
    <h2 class="h5 mb-3">الأقسام وحدود الاشتراك</h2>
    <dl class="row g-3 mb-0">
        @foreach($restaurant->moduleStates() as $key => $enabled)
            <div class="col-md-6">
                <dt class="text-muted fw-normal">{{ config("modules.list.$key.label") }}</dt>
                <dd class="mb-0"><span class="badge {{ $enabled ? 'bg-success' : 'bg-secondary' }}">{{ $enabled ? 'مفعّل' : 'موقوف' }}</span></dd>
            </div>
        @endforeach
        @foreach(config('modules.limits') as $key => $label)
            <div class="col-md-6"><dt class="text-muted fw-normal">{{ $label }}</dt><dd class="mb-0">{{ $restaurant->limit($key) ?: 'غير محدود' }}</dd></div>
        @endforeach
    </dl>
</section>
@endsection

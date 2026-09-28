@extends('layouts.panel')
@section('content')
<h1>{{ $restaurant->exists?'تعديل المطعم':'مطعم جديد' }}</h1>
<form method="post" action="{{ $restaurant->exists?route('admin.restaurants.update',$restaurant):route('admin.restaurants.store') }}" class="card p-4">@csrf @if($restaurant->exists)@method('put')@endif
<div class="row g-3"><div class="col-md-6"><label>اسم المطعم</label><input class="form-control" name="name" required value="{{ old('name',$restaurant->name) }}"></div><div class="col-md-6"><label>الرابط المختصر</label><input class="form-control" name="slug" required value="{{ old('slug',$restaurant->slug) }}"></div><div class="col-md-6"><label>البريد</label><input class="form-control" type="email" name="email" value="{{ old('email',$restaurant->email) }}"></div><div class="col-md-6"><label>تاريخ ووقت انتهاء الاشتراك</label><input class="form-control" type="datetime-local" name="expires_at" value="{{ old('expires_at',$restaurant->expires_at?->format('Y-m-d\TH:i')) }}"><small class="text-muted">اتركه فارغًا لاشتراك بدون انتهاء.</small></div>
<div class="col-12 form-check">
    <input type="hidden" name="ordering_enabled" value="0">
    <input class="form-check-input" type="checkbox" name="ordering_enabled" value="1" @checked(old('ordering_enabled', $restaurant->ordering_enabled))>
    <label>تفعيل طلبات العملاء من المنيو</label>
    <small class="d-block text-muted">الأدمن يفعّل أو يوقف الخاصية. المطعم يحدد عدد الطاولات من إعداداته.</small>
</div>
@unless($restaurant->exists)<div class="col-md-6"><label>اسم المدير</label><input class="form-control" name="admin_name" required></div><div class="col-md-6"><label>بريد المدير</label><input class="form-control" type="email" name="admin_email" required></div><div class="col-md-6"><label>كلمة المرور</label><input class="form-control" type="password" name="password" required></div>@else<div class="col-12 form-check"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($restaurant->is_active)><label>المطعم نشط</label></div>@endunless
@if($restaurant->exists)
<div class="col-12"><hr class="my-2"><h2 class="h6">QR التقييم على Google Maps</h2><p class="text-muted small">الصق رابط المطعم من Google Maps (مشاركة ← نسخ الرابط) والـ Place ID هيتستخرج تلقائيًا. بدونه مش هيشتغل كود التقييم.</p></div>
<div class="col-md-6"><label>رابط Google Maps</label><input class="form-control" type="url" dir="ltr" name="map_url" data-google-place-source value="{{ old('map_url',$restaurant->map_url) }}" placeholder="https://www.google.com/maps/place/..."></div>
<div class="col-md-6"><label>Google Place ID</label><input class="form-control" dir="ltr" name="google_place_id" data-google-place-id value="{{ old('google_place_id',$restaurant->google_place_id) }}" placeholder="ChIJ..."><div class="form-text" data-google-place-status>@if($restaurant->hasGoogleReviewLink())<span class="text-success">جاهز — كود التقييم شغال.</span>@else<span class="text-danger">لسه مفيش Place ID.</span>@endif</div></div>
@if($restaurant->hasGoogleReviewLink())<div class="col-12"><div class="input-group" dir="ltr"><input class="form-control" readonly value="{{ $restaurant->googleReviewUrl() }}"><span class="input-group-text">صفحة التقييم</span></div></div>@endif
@endif
</div><button class="btn btn-primary mt-4">حفظ</button></form>
@endsection

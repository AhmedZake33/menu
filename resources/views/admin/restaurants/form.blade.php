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
@unless($restaurant->exists)<div class="col-md-6"><label>اسم المدير</label><input class="form-control" name="admin_name" required></div><div class="col-md-6"><label>بريد المدير</label><input class="form-control" type="email" name="admin_email" required></div><div class="col-md-6"><label>كلمة المرور</label><input class="form-control" type="password" name="password" required></div>@else<div class="col-12 form-check"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($restaurant->is_active)><label>المطعم نشط</label></div>@endunless</div><button class="btn btn-primary mt-4">حفظ</button></form>
@endsection

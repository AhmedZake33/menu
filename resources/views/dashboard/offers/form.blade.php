@extends('layouts.panel')
@section('title', $offer->exists ? 'تعديل العرض' : 'عرض جديد')
@section('content')
<div class="page-head">
    <div><h1>{{ $offer->exists ? 'تعديل العرض' : 'عرض عام جديد' }}</h1><p>الخصم على السعر الأصلي لكل صنف، ويظهر تلقائيًا في المنيو والكاشير خلال مدة العرض.</p></div>
    <a class="btn btn-outline-secondary" href="{{ route('dashboard.offers.index') }}">كل العروض</a>
</div>
<form class="card p-4" method="post" action="{{ $offer->exists ? route('dashboard.offers.update', $offer) : route('dashboard.offers.store') }}">
    @csrf
    @if($offer->exists) @method('put') @endif
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="offer-title">اسم العرض</label><input class="form-control" id="offer-title" name="title" maxlength="150" required value="{{ old('title', $offer->title) }}"></div>
        <div class="col-md-3">
            <label class="form-label" for="discount-type">نوع الخصم</label>
            <select class="form-select" id="discount-type" name="discount_type">
                <option value="percent" @selected(old('discount_type', $offer->discount_type) === 'percent')>نسبة مئوية %</option>
                <option value="fixed" @selected(old('discount_type', $offer->discount_type) === 'fixed')>مبلغ ثابت لكل صنف ({{ auth()->user()->restaurant->currency }})</option>
            </select>
        </div>
        <div class="col-md-3"><label class="form-label" for="discount-value">قيمة الخصم</label><input class="form-control" type="number" id="discount-value" name="discount_value" min="0.01" step="0.01" required value="{{ old('discount_value', $offer->discount_value) }}"><small class="text-muted">النسبة المئوية بحد أقصى 100%.</small></div>
        <div class="col-12"><label class="form-label" for="offer-description">وصف العرض</label><textarea class="form-control" id="offer-description" name="description" rows="3" maxlength="2000">{{ old('description', $offer->description) }}</textarea></div>
        <div class="col-md-6"><label class="form-label" for="offer-start">بداية العرض</label><input class="form-control" type="datetime-local" id="offer-start" name="starts_at" value="{{ old('starts_at', $offer->starts_at?->format('Y-m-d\TH:i')) }}"><small class="text-muted">اتركه فارغًا ليبدأ فورًا.</small></div>
        <div class="col-md-6"><label class="form-label" for="offer-end">نهاية العرض</label><input class="form-control" type="datetime-local" id="offer-end" name="ends_at" value="{{ old('ends_at', $offer->ends_at?->format('Y-m-d\TH:i')) }}"><small class="text-muted">اتركه فارغًا لعرض بدون انتهاء.</small></div>
        <div class="col-12"><p class="text-muted mb-0">المواعيد بتوقيت {{ config('app.timezone') }}. عند وجود خصم على الصنف أو أكثر من عرض، يُطبّق السعر الأقل فقط بدون جمع الخصومات. الخصم اليدوي على فاتورة الكاشير يظل حسب صلاحيات الاشتراك.</p></div>
        <div class="col-12 form-check form-switch ms-3">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input" type="checkbox" id="offer-active" name="is_active" value="1" @checked(old('is_active', $offer->is_active))>
            <label class="form-check-label" for="offer-active">العرض مفعّل</label>
        </div>
        <div class="col-12"><button class="btn btn-primary px-4">حفظ العرض</button></div>
    </div>
</form>
@endsection

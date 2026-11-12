@extends('layouts.panel')
@section('title', 'إعدادات الكاشير')
@section('content')
<div class="page-head">
    <div>
        <h1>إعدادات الكاشير</h1>
        <p>النسب وطرق الدفع وما يُطبع على الإيصال.</p>
    </div>
</div>

@include('dashboard.cashier._nav')

<form method="post" action="{{ route('dashboard.cashier.settings.update') }}" class="card form-section" style="max-width: 760px">
    @csrf
    @method('put')
    <div class="row g-3">
        <div class="col-md-6">
            <label for="taxPercent">الضريبة %</label>
            <input class="form-control" id="taxPercent" type="number" step="0.01" min="0" max="100" name="tax_percent" required value="{{ old('tax_percent', $cashier['tax_percent']) }}">
        </div>
        <div class="col-md-6">
            <label for="servicePercent">الخدمة %</label>
            <input class="form-control" id="servicePercent" type="number" step="0.01" min="0" max="100" name="service_percent" required value="{{ old('service_percent', $cashier['service_percent']) }}">
            <small class="text-muted">الخدمة تُحسب بعد الخصم، والضريبة على المجموع مع الخدمة.</small>
        </div>
        <div class="col-12">
            <label class="d-block">طرق الدفع المقبولة</label>
            @foreach($allMethods as $key => $label)
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="method-{{ $key }}" name="payment_methods[]" value="{{ $key }}" @checked(in_array($key, old('payment_methods', $cashier['payment_methods'])))>
                    <label class="form-check-label" for="method-{{ $key }}">{{ $label }}</label>
                </div>
            @endforeach
        </div>
        <div class="col-12">
            <label for="receiptFooter">سطر أسفل الإيصال</label>
            <input class="form-control" id="receiptFooter" name="receipt_footer" maxlength="200" value="{{ old('receipt_footer', $cashier['receipt_footer']) }}">
        </div>
        <div class="col-12">
            <span class="badge {{ $cashier['allow_discount'] ? 'bg-success' : 'bg-secondary' }}">الخصم على الفاتورة: {{ $cashier['allow_discount'] ? 'مسموح' : 'غير مسموح' }}</span>
            <small class="text-muted d-block mt-1">هذا الخيار تحدده إدارة المنصة.</small>
        </div>
    </div>
    <div class="mt-4"><button class="btn btn-primary px-4"><i class="bi bi-check2-circle"></i> حفظ الإعدادات</button></div>
</form>
@endsection

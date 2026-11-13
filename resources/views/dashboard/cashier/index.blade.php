@extends('layouts.panel')
@section('title', 'الكاشير')
@section('content')
@php
    $money = fn ($value) => number_format((float) $value, 2);
    $multiPage = $categories->pluck('menu_page_id')->unique()->count() > 1;
@endphp

<div class="page-head">
    <div>
        <h1>الكاشير</h1>
        <p>اختر الأصناف، حصّل، واطبع الإيصال.</p>
    </div>
    @if($receiptOrder)
        <a class="btn btn-dark" target="_blank" href="{{ route('dashboard.cashier.receipt', $receiptOrder) }}?print=1"><i class="bi bi-printer"></i> طباعة إيصال #{{ $receiptOrder->id }}</a>
    @endif
</div>

@include('dashboard.cashier._nav')

@if($unpaidOrders->isNotEmpty())
    <section class="mb-4">
        <h2 class="mb-3">طلبات من المنيو في انتظار التحصيل <span class="badge bg-warning">{{ $unpaidOrders->count() }}</span></h2>
        <div class="unpaid-list">
            @foreach($unpaidOrders as $order)
                <article class="card unpaid-card">
                    <header>
                        <span>#{{ $order->id }} {{ $order->customer_name }}@if($order->table_number)<small class="text-muted"> (طاولة {{ $order->table_number }})</small>@endif</span>
                        <strong>{{ $money($order->total) }} {{ $order->currency }}</strong>
                    </header>
                    <ul>
                        @foreach($order->items as $line)
                            <li>{{ $line->quantity }}× {{ $line->item_name }}</li>
                        @endforeach
                    </ul>
                    <form method="post" action="{{ route('dashboard.cashier.orders.pay', $order) }}" class="d-flex gap-2" data-no-ajax>
                        @csrf
                        <select class="form-select form-select-sm" name="payment_method" aria-label="طريقة الدفع">
                            @foreach($paymentMethods as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-sm btn-primary text-nowrap">تحصيل</button>
                    </form>
                </article>
            @endforeach
        </div>
    </section>
@endif

@if($categories->isEmpty())
    <div class="card p-5 text-center">
        <p class="mb-2 fw-semibold">لا توجد أصناف متاحة للبيع.</p>
        <p class="text-muted mb-0">أضف أصنافًا مفعّلة ومتاحة من قسم الأصناف لتظهر هنا.</p>
    </div>
@else
<div class="pos" data-pos
     data-currency="{{ $restaurant->currency }}"
     data-tax="{{ $cashier['tax_percent'] }}"
     data-service="{{ $cashier['service_percent'] }}">
    <section>
        <div class="pos-tools">
            <input class="form-control" type="search" placeholder="ابحث عن صنف..." data-pos-search aria-label="بحث في الأصناف">
        </div>
        <div class="pos-cats" role="tablist">
            <button type="button" class="active" data-pos-cat="">الكل</button>
            @foreach($categories as $category)
                <button type="button" data-pos-cat="{{ $category->id }}">@if($multiPage){{ $category->menuPage?->name }} / @endif{{ $category->name }}</button>
            @endforeach
        </div>
        <div class="pos-grid">
            @foreach($categories as $category)
                @foreach($category->items as $item)
                    <button type="button" class="pos-item" data-pos-item data-id="{{ $item->id }}" data-name="{{ $item->name }}" data-price="{{ number_format($prices[$item->id]['price'], 2, '.', '') }}" data-cat="{{ $category->id }}">
                        <strong>{{ $item->name }}</strong>
                        <span>{{ $money($prices[$item->id]['price']) }}</span>
                        @if($prices[$item->id]['original_price'] > $prices[$item->id]['price'])
                            <del class="small text-muted">{{ $money($prices[$item->id]['original_price']) }}</del>
                            <small class="text-success">{{ $prices[$item->id]['offer_title'] ?? 'خصم على الصنف' }}</small>
                        @endif
                    </button>
                @endforeach
            @endforeach
        </div>
        <p class="pos-empty" data-pos-no-results hidden>لا يوجد صنف بهذا الاسم.</p>
    </section>

    <form class="pos-ticket" method="post" action="{{ route('dashboard.cashier.sales.store') }}" data-pos-form data-no-ajax>
        @csrf
        <h2>
            <span>الفاتورة</span>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-pos-clear>تفريغ</button>
        </h2>

        <div class="pos-lines" data-pos-lines>
            <p class="pos-empty" data-pos-empty>اضغط على صنف لإضافته للفاتورة.</p>
        </div>

        <div class="pos-field">
            <div class="choice-row">
                <label><input type="radio" name="order_type" value="dine_in" checked data-pos-type><span><i class="bi bi-cup-hot"></i> صالة</span></label>
                <label><input type="radio" name="order_type" value="takeaway" data-pos-type><span><i class="bi bi-bag"></i> تيك أواي</span></label>
            </div>
        </div>

        <div class="row g-2">
            @if($restaurant->tables_count > 0)
                <div class="col-5 pos-field" data-pos-table>
                    <label for="posTable">الطاولة</label>
                    <select class="form-select" id="posTable" name="table_number">
                        <option value="">بدون</option>
                        @for($table = 1; $table <= $restaurant->tables_count; $table++)
                            <option value="{{ $table }}">{{ $table }}</option>
                        @endfor
                    </select>
                </div>
            @endif
            <div class="col pos-field">
                <label for="posCustomer">اسم العميل (اختياري)</label>
                <input class="form-control" id="posCustomer" name="customer_name" maxlength="150">
            </div>
        </div>

        @if($cashier['allow_discount'])
            <div class="pos-field">
                <label for="posDiscount">خصم ({{ $restaurant->currency }})</label>
                <input class="form-control" id="posDiscount" type="number" name="discount" min="0" step="0.01" inputmode="decimal" placeholder="0.00" data-pos-discount>
            </div>
        @endif

        <div class="pos-totals">
            <div><span>المجموع</span><span data-pos-subtotal>0.00</span></div>
            <div data-pos-discount-row hidden><span>الخصم</span><span data-pos-discount-out>0.00</span></div>
            <div @if(! $cashier['service_percent']) hidden @endif><span>خدمة {{ rtrim(rtrim(number_format($cashier['service_percent'], 2), '0'), '.') }}%</span><span data-pos-service>0.00</span></div>
            <div @if(! $cashier['tax_percent']) hidden @endif><span>ضريبة {{ rtrim(rtrim(number_format($cashier['tax_percent'], 2), '0'), '.') }}%</span><span data-pos-tax>0.00</span></div>
            <div class="pos-grand"><span>الإجمالي</span><span><span data-pos-total>0.00</span> {{ $restaurant->currency }}</span></div>
        </div>

        <div class="pos-field">
            <label>طريقة الدفع</label>
            <div class="choice-row">
                @foreach($paymentMethods as $key => $label)
                    <label><input type="radio" name="payment_method" value="{{ $key }}" @checked($loop->first) data-pos-method><span>{{ $label }}</span></label>
                @endforeach
            </div>
        </div>

        <div class="pos-field" data-pos-cash>
            <label for="posPaid">المبلغ المستلم</label>
            <input class="form-control" id="posPaid" type="number" min="0" step="0.01" inputmode="decimal" placeholder="0.00" data-pos-paid>
            <div class="pos-change"><span>الباقي للعميل</span><b data-pos-change>0.00</b></div>
        </div>

        <button class="btn btn-primary pos-pay" data-pos-submit disabled><i class="bi bi-check2-circle"></i> تحصيل الفاتورة</button>
    </form>
</div>
@endif
@endsection

<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $menuPage->meta_title ?: $menuPage->name.' | '.$restaurant->name }}</title>
    <meta name="description" content="{{ $menuPage->meta_description ?: $menuPage->description }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root{--primary:{{ $menuPage->theme->primary_color ?: '#7c3aed' }};--secondary:{{ $menuPage->theme->secondary_color ?: '#35205a' }};--menu-bg:{{ $menuPage->theme->background_color ?: '#f8f7fc' }};--card-bg:{{ $menuPage->theme->card_background_color ?: '#fff' }};--menu-text:{{ $menuPage->theme->text_color ?: '#17151e' }};--heading:{{ $menuPage->theme->heading_color ?: '#17131f' }};--price:{{ $menuPage->theme->price_color ?: '#7c3aed' }};--button:{{ $menuPage->theme->button_color ?: '#7c3aed' }};--menu-border:{{ $menuPage->theme->border_color ?: '#ebe7f1' }};--content-width:{{ $menuPage->theme->content_width ?: '1320px' }};--menu-font:"{{ $menuPage->theme->font_family ?: 'Tahoma' }}";--heading-font:"{{ $menuPage->theme->heading_font_family ?: 'Tahoma' }}"}body{background:var(--menu-bg);color:var(--menu-text);font-family:var(--menu-font)}h1,h2,h3{color:var(--heading);font-family:var(--heading-font)}.menu-hero h1{color:#fff}.public-menu-container{max-width:var(--content-width)}.menu-item{background:var(--card-bg);border:1px solid var(--menu-border);border-radius:{{ $menuPage->theme->card_border_radius }}px}.shadow-soft{box-shadow:0 5px 18px #0000000d}.shadow-medium{box-shadow:0 10px 30px #00000018}.shadow-strong{box-shadow:0 16px 45px #0000002b}.image-circle{border-radius:50%;margin:1rem auto;width:170px;height:170px}.image-square{border-radius:0}.category-nav{position:{{ $menuPage->theme->sticky_categories ? 'sticky' : 'static' }}}
    </style>
</head>
<body class="theme-{{ $menuPage->theme->item_card_style }} {{ $menuPage->theme->enable_dark_mode ? 'theme-dark' : '' }}">
<div data-public-menu-shell>
@php
    $phone = $restaurant->phone ? preg_replace('/\s+/', '', $restaurant->phone) : null;
    $whatsapp = $restaurant->whatsapp ? preg_replace('/\D+/', '', $restaurant->whatsapp) : null;
    $links = [
        ['label' => 'اتصال', 'icon' => 'bi-telephone-fill', 'url' => $phone ? 'tel:'.$phone : null],
        ['label' => 'واتساب', 'icon' => 'bi-whatsapp', 'url' => $whatsapp ? 'https://wa.me/'.$whatsapp : null],
        ['label' => 'الخريطة', 'icon' => 'bi-geo-alt-fill', 'url' => $mapDirectionsUrl],
        ['label' => 'الموقع', 'icon' => 'bi-globe2', 'url' => $restaurant->website_url],
        ['label' => 'Facebook', 'icon' => 'bi-facebook', 'url' => $restaurant->facebook_url],
        ['label' => 'Instagram', 'icon' => 'bi-instagram', 'url' => $restaurant->instagram_url],
        ['label' => 'TikTok', 'icon' => 'bi-tiktok', 'url' => $restaurant->tiktok_url],
    ];
@endphp

<header class="menu-hero">
    <div class="container public-menu-container py-5">
        @if($restaurant->fileUrl($restaurant->logo))
            <img class="restaurant-logo" src="{{ Storage::url($restaurant->logo) }}" alt="{{ $restaurant->name }}">
        @endif
        <h1>{{ $restaurant->name }}</h1>
        <p class="menu-page-description">{{ $menuPage->name }} - {{ $menuPage->description }}</p>

        @if($menuPage->show_social_links)
            <div class="public-cafe-links">
                @foreach($links as $link)
                    @if($link['url'])
                        <a href="{{ $link['url'] }}" target="_blank" rel="noopener">
                            <i class="bi {{ $link['icon'] }}"></i>
                            <span>{{ $link['label'] }}</span>
                        </a>
                    @endif
                @endforeach
            </div>
        @endif

        @if($restaurant->address)
            <p class="menu-address mb-0"><i class="bi bi-pin-map"></i> {{ $restaurant->address }}</p>
        @endif
    </div>
</header>

<main class="container public-menu-container py-4" data-public-menu-content>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php($showItemImages = $menuPage->theme->show_item_images && $restaurant->hasModule('files'))

    @if($restaurant->hasModule('reservations'))
        <div class="text-center mb-4">
            <button class="btn btn-primary px-4" type="button" style="background:var(--button);border-color:var(--button)" data-bs-toggle="modal" data-bs-target="#reservationModal">
                <i class="bi bi-calendar-check"></i> احجز طاولة
            </button>
        </div>
        <div class="modal fade" id="reservationModal" tabindex="-1" aria-labelledby="reservationModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" method="post" action="{{ route('public.reservations.store', $restaurant) }}">
                    @csrf
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="reservationModalLabel">حجز طاولة في {{ $restaurant->name }}</h2>
                        <button type="button" class="btn-close ms-0" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="reservationName">الاسم</label>
                                <input class="form-control" id="reservationName" name="customer_name" maxlength="150" required value="{{ old('customer_name') }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="reservationPhone">رقم الموبايل</label>
                                <input class="form-control" id="reservationPhone" name="customer_phone" type="tel" dir="ltr" maxlength="30" required value="{{ old('customer_phone') }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="reservationDate">التاريخ</label>
                                <input class="form-control" id="reservationDate" name="date" type="date" min="{{ now()->format('Y-m-d') }}" required value="{{ old('date') }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="reservationTime">الوقت</label>
                                <input class="form-control" id="reservationTime" name="time" type="time" required value="{{ old('time') }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="reservationGuests">عدد الأفراد</label>
                                <input class="form-control" id="reservationGuests" name="guests" type="number" min="1" max="50" required value="{{ old('guests', 2) }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="reservationNotes">ملاحظات (اختياري)</label>
                                <textarea class="form-control" id="reservationNotes" name="notes" rows="2" maxlength="500">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary w-100" style="background:var(--button);border-color:var(--button)">إرسال طلب الحجز</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if($menuPages->count() > 1)
        <nav class="menu-page-links mb-4" aria-label="روابط صفحات المنيو">
            @foreach($menuPages as $page)
                <a class="{{ $page->is($menuPage) ? 'active' : '' }}" data-public-menu-link href="{{ $page->is_default ? route('public.restaurant', $restaurant) : route('public.menu', [$restaurant, $page]) }}">
                    {{ $page->name }}
                </a>
            @endforeach
        </nav>
    @endif

    @if($menuPage->theme->enable_search)
        <input id="menu-search" class="form-control form-control-lg mb-4" placeholder="ابحث في المنيو...">
    @endif

    @if($menuPage->theme->enable_category_filter)
        <nav class="category-nav category-{{ $menuPage->theme->category_layout }} mb-4">
            @foreach($menuPage->categories as $category)
                <a href="#category-{{ $category->id }}">{{ $category->name }}</a>
            @endforeach
        </nav>
    @endif

    @if($offers->isNotEmpty())
        <section class="card p-3 mb-4" aria-label="العروض الحالية">
            <h2 class="h5"><i class="bi bi-tags-fill"></i> العروض الحالية</h2>
            @foreach($offers as $offer)
                <div class="{{ $loop->first ? '' : 'border-top pt-2 mt-2' }}">
                    <strong class="text-break">{{ $offer->title }}</strong>
                    <span class="badge text-bg-success">خصم {{ number_format($offer->discount_value, 2) }} {{ $offer->discount_type === 'percent' ? '%' : $restaurant->currency }} على كل صنف</span>
                    @if($offer->description)<p class="mb-0 text-break">{{ $offer->description }}</p>@endif
                    @if($offer->ends_at)<small class="text-muted">حتى {{ $offer->ends_at->format('Y/m/d h:i A') }}</small>@endif
                </div>
            @endforeach
            <small class="text-muted mt-2">الأسعار المعروضة تشمل أفضل خصم متاح، ولا تُجمع العروض مع خصم الصنف أو مع بعضها.</small>
        </section>
    @endif

    @foreach($menuPage->categories as $category)
        <section id="category-{{ $category->id }}" class="mb-5">
            <div class="public-category-heading">
                @if($menuPage->theme->show_category_images && $restaurant->fileUrl($category->image))
                    <img src="{{ Storage::url($category->image) }}" alt="{{ $category->name }}">
                @endif
                <div>
                    <h2 class="section-title">{{ $category->name }}</h2>
                    <p class="text-muted">{{ $category->description }}</p>
                </div>
            </div>
            <div class="row g-3">
                @forelse($category->items as $item)
                    <article class="menu-searchable col-{{ 12 / $menuPage->theme->items_per_row_mobile }} col-md-{{ 12 / $menuPage->theme->items_per_row_tablet }} col-lg-{{ 12 / $menuPage->theme->items_per_row_desktop }}" data-search="{{ $item->name }} {{ $item->short_description }}">
                        <div class="menu-item card h-100 overflow-hidden shadow-{{ $menuPage->theme->card_shadow }}">
                            @if($showItemImages && $item->image)
                                <button class="public-menu-image-button" type="button" data-bs-toggle="modal" data-bs-target="#publicItemImage{{ $item->id }}" aria-label="عرض صورة {{ $item->name }}">
                                    <img src="{{ Storage::url($item->image) }}" class="menu-image image-{{ $menuPage->theme->image_shape }} image-{{ $menuPage->theme->image_position }}" alt="{{ $item->name }}">
                                    <span><i class="bi bi-arrows-fullscreen"></i></span>
                                </button>
                            @endif
                            <div class="card-body">
                                <div class="menu-item-heading">
                                    <h3 class="h5">{{ $item->name }} @if($item->is_new)<span class="badge text-bg-success">جديد</span>@endif</h3>
                                    @if($menuPage->theme->show_prices)
                                        <x-item-price :pricing="$prices[$item->id]" :currency="$restaurant->currency" />
                                    @endif
                                </div>
                                @if($menuPage->theme->show_descriptions)
                                    <p class="text-muted">{{ $item->short_description }}</p>
                                @endif
                                @unless($item->is_available)
                                    <span class="badge text-bg-secondary">غير متوفر حاليًا</span>
                                @endunless
                                @if($restaurant->ordering_enabled && $item->is_available)
                                    <button
                                        class="btn btn-primary w-100 mt-3"
                                        type="button"
                                        data-order-add
                                        data-id="{{ $item->id }}"
                                        data-name="{{ $item->name }}"
                                        data-price="{{ number_format($prices[$item->id]['price'], 2, '.', '') }}"
                                    >
                                        <i class="bi bi-plus-circle"></i> أضف للطلب
                                    </button>
                                @endif
                            </div>
                        </div>
                    </article>

                    @if($showItemImages && $item->image)
                        <div class="modal fade" id="publicItemImage{{ $item->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-xl">
                                <div class="modal-content image-preview-modal item-detail-preview-modal">
                                    <div class="modal-header">
                                        <span></span>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                                    </div>
                                    <div class="modal-body public-item-preview">
                                        <div class="public-item-preview-image">
                                            <img src="{{ Storage::url($item->image) }}" alt="{{ $item->name }}">
                                        </div>
                                        <div class="public-item-preview-details">
                                            <span class="badge text-bg-light">{{ $category->name }}</span>
                                            <h3>{{ $item->name }}</h3>
                                            @if($menuPage->theme->show_prices)
                                                <x-item-price :pricing="$prices[$item->id]" :currency="$restaurant->currency" />
                                            @endif
                                            @if($menuPage->theme->show_descriptions && $item->short_description)
                                                <p>{{ $item->short_description }}</p>
                                            @endif
                                            <div class="d-flex flex-wrap gap-2">
                                                @if($item->is_new)<span class="badge text-bg-success">جديد</span>@endif
                                                @if($item->is_featured)<span class="badge text-bg-warning">مميز</span>@endif
                                                @unless($item->is_available)<span class="badge text-bg-secondary">غير متوفر حاليًا</span>@endunless
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @empty
                    <p>لا توجد أصناف متاحة.</p>
                @endforelse
            </div>
        </section>
    @endforeach
</main>

@if($restaurant->ordering_enabled)
    <div class="public-order-bar" data-order-bar hidden>
        <div>
            <strong>طلبك</strong>
            <span><span data-order-count>0</span> صنف - <span data-order-total>0.00</span> {{ $restaurant->currency }}</span>
        </div>
        <button class="btn btn-light" type="button" data-bs-toggle="modal" data-bs-target="#publicOrderModal">
            إتمام الطلب
        </button>
    </div>

    <div class="modal fade" id="publicOrderModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form method="post" action="{{ route('public.orders.code', $restaurant) }}" data-confirm-action="{{ route('public.orders.confirm', $restaurant) }}" data-public-order-form>
                    @csrf
                    <div class="modal-header">
                        <h2 class="modal-title fs-5">تأكيد الطلب</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info d-none" data-order-status></div>
                        <div class="public-order-items mb-3" data-order-items></div>
                        <div data-order-inputs></div>
                        <input type="hidden" name="verification_token" data-verification-token>
                        <div class="row g-3" data-order-details-step>
                            <div class="col-md-6">
                                <label class="form-label">الاسم *</label>
                                <input class="form-control" name="customer_name" required value="{{ old('customer_name') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">الإيميل *</label>
                                <input class="form-control" type="email" name="customer_email" required value="{{ old('customer_email') }}">
                                <small class="text-muted">سيتم إرسال تأكيد الطلب على هذا الإيميل.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">الهاتف</label>
                                <input class="form-control" name="customer_phone" value="{{ old('customer_phone') }}">
                            </div>
                            @if($restaurant->tables_count > 0)
                                <div class="col-md-6">
                                    <label class="form-label">رقم الطاولة</label>
                                    <select class="form-select" name="table_number">
                                        <option value="">بدون رقم طاولة</option>
                                        @for($table = 1; $table <= $restaurant->tables_count; $table++)
                                            <option value="{{ $table }}" @selected((string) old('table_number') === (string) $table)>طاولة {{ $table }}</option>
                                        @endfor
                                    </select>
                                    <small class="text-muted">اختياري — سيبه فاضي لو الطلب توصيل أو سفري.</small>
                                </div>
                            @endif
                            <div class="col-12">
                                <label class="form-label">ملاحظات</label>
                                <textarea class="form-control" name="notes" rows="3">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                        <div class="d-none" data-order-verification-step>
                            <label class="form-label">كود التأكيد *</label>
                            <input class="form-control text-center fs-4 fw-bold" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" name="verification_code" placeholder="000000" autocomplete="one-time-code">
                            <small class="text-muted d-block mt-2">اكتب الكود المكون من 6 أرقام الذي تم إرساله إلى الإيميل.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <strong class="me-auto">الإجمالي: <span data-order-total>0.00</span> {{ $restaurant->currency }}</strong>
                        <button class="btn btn-primary" data-order-submit disabled>إرسال كود التأكيد</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

<footer class="text-center py-4 text-muted">
    <div>منيو {{ $restaurant->name }} الإلكتروني</div>
    <x-system-copyright class="public-menu-copyright mb-0 mt-2" />
</footer>
</div>
</body>
</html>

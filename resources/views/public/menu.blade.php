<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
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
        @if($restaurant->logo)
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
    @if($menuPages->count() > 1 || $mapEmbedUrl)
        <nav class="menu-page-links mb-4" aria-label="روابط صفحات المنيو والموقع">
            @foreach($menuPages as $page)
                <a class="{{ $page->is($menuPage) ? 'active' : '' }}" data-public-menu-link href="{{ $page->is_default ? route('public.restaurant', $restaurant) : route('public.menu', [$restaurant, $page]) }}">
                    {{ $page->name }}
                </a>
            @endforeach
            @if($mapEmbedUrl)
                <button class="menu-location-tab" type="button" data-public-map-toggle aria-expanded="false" aria-controls="menu-map-panel">
                    <i class="bi bi-geo-alt"></i> الموقع
                </button>
            @endif
        </nav>
    @endif

    @if($mapEmbedUrl)
        <section id="menu-map-panel" class="public-map-card mb-4 d-none" data-public-map-panel>
            <div class="public-map-info">
                <div>
                    <span class="eyebrow text-primary">الموقع</span>
                    <h2>تعال زورنا</h2>
                    @if($restaurant->address)
                        <p>{{ $restaurant->address }}</p>
                    @endif
                </div>
                @if($mapDirectionsUrl)
                    <a class="btn btn-primary" href="{{ $mapDirectionsUrl }}" target="_blank" rel="noopener">
                        <i class="bi bi-signpost-split"></i> اذهب للمكان
                    </a>
                @endif
            </div>
            <iframe src="{{ $mapEmbedUrl }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen title="خريطة {{ $restaurant->name }}"></iframe>
        </section>
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

    @foreach($menuPage->categories as $category)
        <section id="category-{{ $category->id }}" class="mb-5">
            <div class="public-category-heading">
                @if($menuPage->theme->show_category_images && $category->image)
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
                            @if($menuPage->theme->show_item_images && $item->image)
                                <button class="public-menu-image-button" type="button" data-bs-toggle="modal" data-bs-target="#publicItemImage{{ $item->id }}" aria-label="عرض صورة {{ $item->name }}">
                                    <img src="{{ Storage::url($item->image) }}" class="menu-image image-{{ $menuPage->theme->image_shape }} image-{{ $menuPage->theme->image_position }}" alt="{{ $item->name }}">
                                    <span><i class="bi bi-arrows-fullscreen"></i></span>
                                </button>
                            @endif
                            <div class="card-body">
                                <div class="menu-item-heading">
                                    <h3 class="h5">{{ $item->name }} @if($item->is_new)<span class="badge text-bg-success">جديد</span>@endif</h3>
                                    @if($menuPage->theme->show_prices)
                                        <strong class="price">{{ number_format($item->price, 2) }} {{ $restaurant->currency }}</strong>
                                    @endif
                                </div>
                                @if($menuPage->theme->show_descriptions)
                                    <p class="text-muted">{{ $item->short_description }}</p>
                                @endif
                                @unless($item->is_available)
                                    <span class="badge text-bg-secondary">غير متوفر حاليًا</span>
                                @endunless
                            </div>
                        </div>
                    </article>

                    @if($menuPage->theme->show_item_images && $item->image)
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
                                                <strong class="price">{{ number_format($item->price, 2) }} {{ $restaurant->currency }}</strong>
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

<footer class="text-center py-4 text-muted">
    <div>منيو {{ $restaurant->name }} الإلكتروني</div>
    <x-system-copyright class="public-menu-copyright mb-0 mt-2" />
</footer>
</div>
</body>
</html>

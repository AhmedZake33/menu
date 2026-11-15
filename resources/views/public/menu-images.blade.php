<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $restaurant->name }} | المنيو</title>
    <meta name="description" content="{{ $restaurant->description ?: 'منيو '.$restaurant->name }}">
    @if($images->isNotEmpty())
        <meta property="og:image" content="{{ url($images->first()->url()) }}">
    @endif
    @vite(['resources/css/app.css'])
    <style>
        :root{--brand:{{ $restaurant->brandColor() }}}
        body{background:#f4f2f0;color:#1d1a1f;margin:0}
        .im-hero{background:var(--brand);color:#fff;text-align:center;padding:28px 16px 22px}
        .im-hero img.im-logo{width:84px;height:84px;object-fit:cover;border-radius:50%;border:3px solid #ffffff55;background:#fff;margin-bottom:10px}
        .im-hero h1{font-size:1.6rem;margin:0 0 4px;color:#fff}
        .im-hero p{margin:0 auto;max-width:560px;opacity:.9}
        .im-links{display:flex;flex-wrap:wrap;gap:8px;justify-content:center;margin-top:14px}
        .im-links a{display:inline-flex;align-items:center;gap:6px;color:#fff;text-decoration:none;background:#ffffff26;border-radius:999px;padding:6px 14px;font-size:.9rem}
        .im-links a:hover{background:#ffffff40}
        .im-address{margin:12px 0 0;font-size:.9rem;opacity:.9}
        .im-wrap{max-width:820px;margin:0 auto;padding:16px 12px 40px}
        .im-page{display:block;margin:0 0 14px;border-radius:12px;overflow:hidden;background:#fff;box-shadow:0 4px 18px #0000001a;cursor:zoom-in}
        .im-page img{display:block;width:100%;height:auto}
        .im-empty{background:#fff;border-radius:12px;padding:40px 16px;text-align:center;color:#6b6570}
        .im-hint{text-align:center;color:#6b6570;font-size:.85rem;margin:0 0 12px}
        .im-zoom{position:fixed;inset:0;background:#000000e6;z-index:1080;display:none;overflow:auto;-webkit-overflow-scrolling:touch}
        .im-zoom.open{display:block}
        .im-zoom img{display:block;margin:0 auto;max-width:100%;height:auto;touch-action:pinch-zoom}
        .im-zoom button{position:fixed;top:12px;left:12px;z-index:1;border:0;border-radius:999px;width:42px;height:42px;font-size:1.4rem;line-height:1;background:#fff;color:#111}
    </style>
</head>
<body>
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

<header class="im-hero">
    @if($restaurant->fileUrl($restaurant->logo))
        <img class="im-logo" src="{{ $restaurant->fileUrl($restaurant->logo) }}" alt="{{ $restaurant->name }}">
    @endif
    <h1>{{ $restaurant->name }}</h1>
    @if($restaurant->description)<p>{{ $restaurant->description }}</p>@endif
    <div class="im-links">
        @foreach($links as $link)
            @if($link['url'])
                <a href="{{ $link['url'] }}" target="_blank" rel="noopener"><i class="bi {{ $link['icon'] }}"></i><span>{{ $link['label'] }}</span></a>
            @endif
        @endforeach
    </div>
    @if($restaurant->address)
        <p class="im-address"><i class="bi bi-pin-map"></i> {{ $restaurant->address }}</p>
    @endif
</header>

<main class="im-wrap" data-menu-images>
    @forelse($images as $image)
        @if($loop->first)<p class="im-hint"><i class="bi bi-zoom-in"></i> اضغط على الصورة لتكبيرها</p>@endif
        <a class="im-page" href="{{ $image->url() }}" data-zoom>
            <img src="{{ $image->url() }}" alt="{{ $restaurant->name }} - صفحة {{ $loop->iteration }} من المنيو" @if(! $loop->first) loading="lazy" @endif>
        </a>
    @empty
        <div class="im-empty"><i class="bi bi-image fs-1 d-block mb-2"></i>المنيو قيد التحديث، ارجع بعد قليل.</div>
    @endforelse
</main>

<div class="im-zoom" data-zoom-layer role="dialog" aria-modal="true" aria-label="عرض المنيو">
    <button type="button" data-zoom-close aria-label="إغلاق">&times;</button>
    <img alt="" data-zoom-image>
</div>

<script>
    (function () {
        var layer = document.querySelector('[data-zoom-layer]');
        var big = document.querySelector('[data-zoom-image]');
        function close() { layer.classList.remove('open'); big.removeAttribute('src'); document.body.style.overflow = ''; }
        document.querySelectorAll('[data-zoom]').forEach(function (link) {
            link.addEventListener('click', function (event) {
                event.preventDefault();
                big.src = link.getAttribute('href');
                layer.classList.add('open');
                layer.scrollTop = 0;
                document.body.style.overflow = 'hidden';
            });
        });
        layer.addEventListener('click', function (event) { if (event.target !== big) close(); });
        document.addEventListener('keydown', function (event) { if (event.key === 'Escape') close(); });
    })();
</script>
</body>
</html>

<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="منيو إلكتروني بـ QR Code للمطاعم والكافيهات، مع إدارة الطلبات والحجوزات والخصومات والكاشير والتقارير اليومية.">
    <meta name="theme-color" content="#a01d48">
    <title>{{ config('marketing.product_name') }} | منيو وطلبات وكاشير لمطعمك</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-brand-style />
</head>
<body class="marketing-page">
<a class="marketing-skip" href="#main">انتقل إلى المحتوى</a>
<header class="marketing-header">
    <div class="marketing-container marketing-nav">
        <a class="marketing-logo" href="{{ route('public.features') }}" aria-label="{{ config('marketing.product_name') }} — الرئيسية">
            <span class="marketing-logo-icon"><i class="bi bi-qr-code" aria-hidden="true"></i></span>
            <span dir="ltr">{{ config('marketing.product_name') }}<small>مطعمك، متصل.</small></span>
        </a>
        <nav class="marketing-nav-links" aria-label="القائمة الرئيسية">
            <a href="#features">المميزات</a><a href="#menu-preview">شكل المنيو</a><a href="#how-it-works">إزاي بيشتغل؟</a>
        </nav>
        @if(config('modules.signup.enabled'))
            <a class="btn btn-primary" href="{{ route('register') }}">سجّل مطعمك مجانًا</a>
        @endif
        <a class="btn btn-outline-primary" href="{{ route('login') }}">تسجيل الدخول <i class="bi bi-arrow-up-left" aria-hidden="true"></i></a>
    </div>
</header>
<main id="main">
    <section class="marketing-hero marketing-container">
        <div class="marketing-hero-copy">
            <span class="marketing-eyebrow"><span></span> للمطاعم والكافيهات</span>
            <h1>من أول مسحة QR.<br>لحد <em>آخر فاتورة.</em></h1>
            <p class="marketing-lead">منيو يليق بمكانك، وطلبات أسهل لعملائك.<br>تابع المنيو والعروض والحجوزات والكاشير من لوحة واحدة.</p>
            <div class="marketing-actions">
                @if(config('modules.signup.enabled'))
                    <a class="btn btn-primary btn-lg" href="{{ route('register') }}">ابدأ تجربة مجانية {{ config('modules.signup.trial_days') }} يوم <i class="bi bi-arrow-left" aria-hidden="true"></i></a>
                    <a class="btn btn-outline-primary btn-lg" href="{{ route('public.demo') }}">جرّب المنيو</a>
                @else
                    <a class="btn btn-primary btn-lg" href="{{ route('public.demo') }}">جرّب المنيو <i class="bi bi-arrow-left" aria-hidden="true"></i></a>
                @endif
                <a class="marketing-text-link" href="#features">اكتشف المميزات <i class="bi bi-arrow-down" aria-hidden="true"></i></a>
            </div>
            <div class="marketing-hero-notes"><span><i class="bi bi-check2" aria-hidden="true"></i> يفتح من الموبايل</span><span><i class="bi bi-check2" aria-hidden="true"></i> بدون تحميل تطبيق</span><span><i class="bi bi-check2" aria-hidden="true"></i> تصميم بهوية مطعمك</span></div>
        </div>
        <div class="marketing-hero-art" aria-label="معاينة توضيحية للمنيو وإدارة الطلبات">
            <div class="marketing-art-caption"><span class="marketing-art-line"></span> تجربة بسيطة لعميلك. تحكّم أوضح ليك.</div>
            <div class="marketing-phone">
                <div class="marketing-phone-top"><span>9:41</span><span><i class="bi bi-reception-4"></i> <i class="bi bi-wifi"></i> <i class="bi bi-battery-full"></i></span></div>
                <div class="marketing-phone-cover"><i class="bi bi-cup-hot" aria-hidden="true"></i><small>COFFEE & GOOD COMPANY</small><strong>قهوة ومزاج</strong><span>لحظتك الحلوة تبدأ هنا.</span></div>
                <div class="marketing-phone-content">
                    <div class="marketing-demo-categories"><span class="selected">قهوة</span><span>إفطار</span><span>حلوى</span></div>
                    <div class="marketing-sample-item"><div class="marketing-food-icon"><i class="bi bi-cup-hot-fill" aria-hidden="true"></i></div><div><strong>كابتشينو</strong><small>قهوة غنية ورغوة ناعمة</small><b>80 ج.م <del>100</del></b></div><span class="marketing-add" aria-hidden="true">+</span></div>
                    <div class="marketing-sample-item"><div class="marketing-food-icon caramel"><i class="bi bi-cup-straw" aria-hidden="true"></i></div><div><strong>آيس لاتيه</strong><small>انتعاش على مزاجك</small><b>95 ج.م</b></div><span class="marketing-add" aria-hidden="true">+</span></div>
                    <div class="marketing-phone-offer"><i class="bi bi-tag" aria-hidden="true"></i> سعر اليوم أحلى مع خصم 20%</div>
                </div>
                <span class="marketing-demo-label">معاينة توضيحية</span>
            </div>
            <div class="marketing-order-note"><span class="marketing-note-icon"><i class="bi bi-bell" aria-hidden="true"></i></span><div><strong>طلب جديد وصل</strong><small>٢ كابتشينو · طاولة ٤</small></div><span class="marketing-status-dot"></span></div>
            <div class="marketing-qr-note"><i class="bi bi-qr-code" aria-hidden="true"></i><div><strong>امسح. اختار. اطلب.</strong><small>المنيو في موبايل عميلك</small></div></div>
        </div>
    </section>

    <div class="marketing-benefit-strip"><div class="marketing-container"><span><i class="bi bi-qr-code-scan"></i> منيو إلكتروني</span><span><i class="bi bi-bag-check"></i> طلبات وحجوزات</span><span><i class="bi bi-tags"></i> خصومات وعروض</span><span><i class="bi bi-receipt"></i> كاشير وتقارير</span></div></div>

    <section class="marketing-section marketing-container" id="features">
        <div class="marketing-section-heading"><div><span class="marketing-eyebrow">تفاصيل صغيرة. فرق في يومك.</span><h2>كل اللي تحتاجه لتجربة<br>أحسن جوّه مطعمك.</h2></div><p>خلّي المنيو دايمًا محدث، والعروض واضحة، ومتابعة الطلبات أسهل من مكان واحد.</p></div>
        <div class="marketing-feature-grid">
            @foreach([
                ['bi-qr-code-scan', 'منيو حاضر في كل وقت', 'شارك رابط المنيو أو اطبع QR للطاولات. عميلك يفتح الأصناف والأسعار من موبايله مباشرة.'],
                ['bi-palette2', 'شكل يعبر عن مكانك', 'شعارك وصورك وألوانك، مع صفحات منيو متعددة وتصنيفات ووصف لكل صنف.'],
                ['bi-tags', 'عروض تشجّع على الاختيار', 'خصم لكل صنف أو عروض عامة بنسبة أو مبلغ ثابت، مع تحديد بداية العرض ونهايته.'],
                ['bi-bell', 'الطلب يوصل لك بوضوح', 'استقبل الطلبات من المنيو وتابع حالتها، مع إشعارات للطلبات الجديدة وتأكيد بالإيميل.'],
                ['bi-calendar2-check', 'حجوزات مرتّبة', 'اسم العميل وميعاده وعدد الأفراد، مع متابعة الحجوزات وتأكيدها من لوحة التحكم.'],
                ['bi-receipt', 'من الاختيار إلى الفاتورة', 'كاشير للصالة والتيك أواي، مع حساب الخصم والخدمة والضريبة وإيصالات جاهزة للطباعة.'],
            ] as [$icon, $title, $description])
                <article class="marketing-feature"><span class="marketing-feature-icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></span><h3>{{ $title }}</h3><p>{{ $description }}</p></article>
            @endforeach
        </div>
    </section>

    <section class="marketing-preview-section" id="menu-preview">
        <div class="marketing-container marketing-preview-grid">
            <div class="marketing-preview-copy"><span class="marketing-eyebrow">عميلك هيشوف إيه؟</span><h2>منيو سهل يتصفح.<br>وشكل يفتح النفس.</h2><p>صور وأسعار وأقسام واضحة، مع السعر الأصلي والمخفض. تقدر تعدّل الأصناف وتحدد المتاح منها في أي وقت.</p><ul><li><i class="bi bi-check2-circle"></i> منيو متوافق مع الموبايل</li><li><i class="bi bi-check2-circle"></i> أكتر من صفحة للمشروبات والأكل وغيرها</li><li><i class="bi bi-check2-circle"></i> تعديل الأسعار من غير إعادة طباعة المنيو</li></ul><a class="btn btn-primary" href="{{ route('public.demo') }}">افتح تجربة المنيو <i class="bi bi-arrow-up-left" aria-hidden="true"></i></a></div>
            <div class="marketing-menu-preview">
                @if(session('demo_unavailable'))<p class="alert alert-info" role="status">المنيو التجريبي مش متاح حاليًا. استعرض المعاينة التوضيحية هنا للتعرف على شكل المنيو.</p>@endif
                <div class="marketing-preview-head"><div><span>معاينة توضيحية</span><h3>قهوة ومزاج</h3><p>اختيارات بسيطة ليوم ألذ.</p></div><i class="bi bi-cup-hot" aria-hidden="true"></i></div>
                <ul class="nav nav-pills marketing-preview-tabs" role="tablist" aria-label="أقسام المنيو التوضيحي">
                    @foreach(['coffee' => 'القهوة', 'breakfast' => 'الإفطار', 'dessert' => 'الحلوى'] as $key => $label)
                        <li class="nav-item" role="presentation"><button class="nav-link {{ $loop->first ? 'active' : '' }}" id="preview-tab-{{ $key }}" data-bs-toggle="pill" data-bs-target="#preview-{{ $key }}" type="button" role="tab" aria-controls="preview-{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">{{ $label }}</button></li>
                    @endforeach
                </ul>
                <div class="tab-content">
                    @foreach([
                        'coffee' => [['bi-cup-hot-fill', 'كابتشينو', 'قهوة إسبريسو مع حليب ورغوة ناعمة', 80, 100], ['bi-cup-straw', 'آيس لاتيه', 'إسبريسو وحليب بارد وثلج', 95, null]],
                        'breakfast' => [['bi-egg-fried', 'فطار على مزاجك', 'بيض وخبز محمص وسلطة طازجة', 120, null], ['bi-basket2', 'ساندوتش الجبنة', 'جبنة وخضار في خبز طازج', 65, null]],
                        'dessert' => [['bi-cake2', 'كيكة الشوكولاتة', 'قطعة شوكولاتة للحظة حلوة', 75, null], ['bi-cookie', 'كوكيز', 'كوكيز بقطع الشوكولاتة', 40, 50]],
                    ] as $key => $samples)
                        <div class="tab-pane {{ $loop->first ? 'show active' : '' }}" id="preview-{{ $key }}" role="tabpanel" aria-labelledby="preview-tab-{{ $key }}" tabindex="0">
                            @foreach($samples as [$icon, $name, $description, $price, $original])
                                <div class="marketing-preview-item"><div class="marketing-food-icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></div><div><h4>{{ $name }}</h4><p>{{ $description }}</p><strong>{{ $price }} ج.م</strong> @if($original)<del>{{ $original }} ج.م</del>@endif</div></div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
                <small class="marketing-preview-disclaimer">الأصناف والأسعار للعرض التوضيحي فقط.</small>
            </div>
        </div>
    </section>

    <section class="marketing-section marketing-container marketing-operations">
        <div><span class="marketing-eyebrow">الصورة أوضح من ناحيتك</span><h2>تابع يومك.<br>واعرف التفاصيل.</h2><p>لوحة خاصة بمطعمك تخلّيك تراجع التحصيل وتدير بياناتك وروابطك من مكان واحد.</p></div>
        <div class="marketing-operations-list">
            <article><i class="bi bi-bar-chart-line" aria-hidden="true"></i><div><h3>تقارير التحصيل اليومية</h3><p>راجع الفواتير المحصّلة حسب اليوم وطريقة الدفع، مع تفاصيل الخصم والخدمة والضريبة.</p></div></article>
            <article><i class="bi bi-eye" aria-hidden="true"></i><div><h3>اعرف مشاهدات المنيو</h3><p>تابع عدد المشاهدات، وصفحات المنيو والتصنيفات والأصناف من لوحة التحكم.</p></div></article>
            <article><i class="bi bi-geo-alt" aria-hidden="true"></i><div><h3>وصول أسهل وتقييم أسرع</h3><p>موقعك على Google Maps وروابط التواصل، مع QR مخصص لصفحة تقييم مطعمك على Google.</p></div></article>
        </div>
    </section>

    <section class="marketing-container" id="how-it-works"><div class="marketing-steps"><span class="marketing-eyebrow">البداية بسيطة</span><h2>من إعداد المنيو<br>لأول مسحة QR.</h2><div class="marketing-steps-grid">@foreach([['جهّز منيو مكانك', 'أضف الاسم والشعار والأقسام والأصناف والأسعار.'], ['خلّيه على ذوقك', 'اختار الألوان والتصميم، وجهّز عروضك وبيانات التواصل.'], ['شارك وابدأ المتابعة', 'انشر الرابط أو اطبع QR، وتابع الطلبات والحجوزات.']] as [$title, $description])<article><span class="marketing-step-number">0{{ $loop->iteration }}</span><h3>{{ $title }}</h3><p>{{ $description }}</p></article>@endforeach</div></div></section>

    <section class="marketing-section marketing-container marketing-faq"><div><span class="marketing-eyebrow">قبل ما تبدأ</span><h2>أسئلة بتتكرر.</h2></div><div>
        <details><summary>العميل محتاج يحمّل تطبيق؟</summary><p>لا، المنيو بيفتح مباشرة من متصفح الموبايل عن طريق الرابط أو مسح QR Code.</p></details>
        <details><summary>أقدر أغيّر الأسعار بعد طباعة الـ QR؟</summary><p>أيوه، طالما رابط المنيو نفسه ثابت، تقدر تعدّل الأسعار والأصناف من لوحة التحكم من غير إعادة طباعة الكود.</p></details>
        <details><summary>هل أقدر أعمل عروض بمدة محددة؟</summary><p>أيوه، تقدر تحدد بداية العرض ونهايته، وتختار خصم بنسبة أو مبلغ ثابت لكل صنف. أفضل خصم متاح بيتطبق بدون جمع العروض تلقائيًا.</p></details>
        <details><summary>كل المميزات بتكون متاحة لكل اشتراك؟</summary><p>الأقسام المتاحة، زي الكاشير والطلبات والحجوزات، بتتحدد حسب إعدادات اشتراك مطعمك. تواصل معانا لمعرفة الأنسب لمكانك.</p></details>
    </div></section>

    <section class="marketing-container"><div class="marketing-contact"><div><span class="marketing-eyebrow">خلّي الخطوة الجاية أسهل</span><h2>منيو يليق بمكانك.<br>وإدارة تريح يومك.</h2></div><div><a class="btn btn-primary btn-lg" href="{{ config('marketing.contact_url') }}" target="_blank" rel="noopener">تواصل معنا <i class="bi bi-arrow-up-left" aria-hidden="true"></i></a><a class="marketing-text-link" href="{{ route('login') }}">عندك حساب؟ ادخل للوحة التحكم</a></div></div></section>
</main>
<footer class="marketing-footer marketing-container"><a class="marketing-logo" href="{{ route('public.features') }}"><i class="bi bi-qr-code" aria-hidden="true"></i><span dir="ltr">{{ config('marketing.product_name') }}</span></a><p>تفاصيل مكانك، في تجربة واحدة.</p><x-system-copyright /></footer>
</body>
</html>

<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Digital Menu SaaS') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-showcase">
            <a class="auth-brand" href="/">
                <span class="auth-brand-mark"><i class="bi bi-qr-code"></i></span>
                <span>Digital Menu</span>
            </a>
            <div class="auth-showcase-content">
                <span class="eyebrow">منصة إدارة المنيو الإلكتروني</span>
                <h1>كل مطاعمك وقوائمك<br>في مكان واحد.</h1>
                <p>أنشئ صفحات منيو جذابة، خصّص التصميم، وشاركها مع عملائك عبر رابط أو QR Code.</p>
                <div class="auth-features">
                    <span><i class="bi bi-shield-check"></i> عزل آمن للبيانات</span>
                    <span><i class="bi bi-phone"></i> متوافق مع الجوال</span>
                    <span><i class="bi bi-graph-up-arrow"></i> إحصائيات مباشرة</span>
                </div>
                <div class="auth-preview">
                    <div class="auth-preview-window">
                        <div class="preview-toolbar"><i></i><i></i><i></i><span>cafe-mocha/menu</span></div>
                        <div class="preview-menu-head"><div class="preview-logo"><i class="bi bi-cup-hot-fill"></i></div><div><strong>Cafe Mocha</strong><small>المنيو الرئيسية</small></div><span class="preview-qr"><i class="bi bi-qr-code"></i></span></div>
                        <div class="preview-categories"><span>القهوة</span><span>الإفطار</span><span>الحلويات</span></div>
                        <div class="preview-items"><div><i class="bi bi-cup-straw"></i><span><strong>آيس لاتيه</strong><small>85 EGP</small></span></div><div><i class="bi bi-egg-fried"></i><span><strong>إفطار مميز</strong><small>120 EGP</small></span></div></div>
                    </div>
                    <div class="auth-floating-stat stat-views"><i class="bi bi-eye-fill"></i><span><strong>+24%</strong><small>مشاهدات المنيو</small></span></div>
                    <div class="auth-floating-stat stat-active"><b></b><span><strong>متصل الآن</strong><small>المنيو منشور</small></span></div>
                </div>
            </div>
            <div class="auth-trust"><span>موثوق وآمن</span><div><i class="bi bi-check-circle-fill"></i> نسخ احتياطي <i class="bi bi-check-circle-fill"></i> حماية البيانات <i class="bi bi-check-circle-fill"></i> دعم RTL</div></div>
            <div class="auth-orb auth-orb-one"></div>
            <div class="auth-orb auth-orb-two"></div>
            <div class="auth-grid"></div>
        </section>
        <section class="auth-form-side">
            <div class="auth-card">
                {{ $slot }}
            </div>
            <p class="auth-copyright">© {{ date('Y') }} Digital Menu SaaS</p>
        </section>
    </main>
</body>
</html>

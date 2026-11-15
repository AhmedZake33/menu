<x-guest-layout>
    <div class="mb-4">
        <div class="auth-welcome-icon"><i class="bi bi-shop"></i></div>
        <span class="eyebrow text-primary">تجربة مجانية {{ $trialDays }} يوم</span>
        <h2 class="auth-title">سجّل مطعمك أو الكافيه</h2>
        <p class="text-muted mb-0">أدخل بياناتك الأساسية وابدأ فورًا. مفيش بطاقة ولا دفع، والتجربة بتبدأ تلقائيًا.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger py-2">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" data-register-form>
        @csrf

        <h3 class="h6 text-muted mb-3"><i class="bi bi-shop"></i> بيانات المطعم</h3>

        <div class="mb-3">
            <label class="form-label fw-semibold" for="restaurant_name">اسم المطعم / الكافيه</label>
            <input id="restaurant_name" class="form-control form-control-lg" name="restaurant_name" value="{{ old('restaurant_name') }}" maxlength="150" required autofocus data-register-name>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold" for="slug">الرابط المختصر</label>
            <input id="slug" class="form-control form-control-lg" dir="ltr" name="slug" value="{{ old('slug') }}" maxlength="60" required placeholder="cafe-mocha" pattern="[a-zA-Z0-9]+(-[a-zA-Z0-9]+)*" autocapitalize="none" data-register-slug>
            <div class="form-text" dir="ltr" style="text-align:left">{{ url('/r') }}/<strong data-register-slug-preview>{{ old('slug', 'your-name') }}</strong></div>
            <div class="form-text">حروف إنجليزية وأرقام وشرطة فقط. ده اللينك اللي هيتحول لـ QR.</div>
        </div>

        <div class="mb-4">
            <label class="form-label fw-semibold" for="phone">رقم التليفون</label>
            <input id="phone" class="form-control form-control-lg" dir="ltr" type="tel" name="phone" value="{{ old('phone') }}" maxlength="30" required autocomplete="tel">
        </div>

        <h3 class="h6 text-muted mb-3"><i class="bi bi-person-badge"></i> بيانات المدير</h3>

        <div class="mb-3">
            <label class="form-label fw-semibold" for="name">اسم المدير</label>
            <input id="name" class="form-control form-control-lg" name="name" value="{{ old('name') }}" maxlength="150" required autocomplete="name">
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold" for="email">البريد الإلكتروني</label>
            <input id="email" class="form-control form-control-lg" dir="ltr" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
            <div class="form-text">هتدخل بيه على لوحة التحكم.</div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label fw-semibold" for="password">كلمة المرور</label>
                <input id="password" class="form-control form-control-lg" type="password" name="password" required autocomplete="new-password" minlength="8">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold" for="password_confirmation">تأكيد كلمة المرور</label>
                <input id="password_confirmation" class="form-control form-control-lg" type="password" name="password_confirmation" required autocomplete="new-password">
            </div>
        </div>

        <h3 class="h6 text-muted mb-3"><i class="bi bi-menu-button-wide"></i> هتعرض المنيو إزاي؟</h3>

        <div class="row g-2 mb-4">
            @php
                $choices = [
                    'digital' => ['bi-phone', 'المنيو الرقمي', 'تضيف أصنافك وأسعارك من اللوحة'],
                    'images' => ['bi-images', 'منيو بالصور', $maxImages > 0 ? 'ترفع حتى '.$maxImages.' صور من منيوك الجاهز' : 'ترفع صور منيوك الجاهز'],
                ];
            @endphp
            @foreach($choices as $value => [$icon, $title, $hint])
                <div class="col-sm-6">
                    <input class="btn-check" type="radio" name="menu_mode" id="mode-{{ $value }}" value="{{ $value }}" @checked(old('menu_mode', 'digital') === $value) required>
                    <label class="btn btn-outline-primary w-100 text-start h-100 p-3" for="mode-{{ $value }}">
                        <i class="bi {{ $icon }} fs-4 d-block mb-1"></i>
                        <strong class="d-block">{{ $title }}</strong>
                        <small class="d-block opacity-75">{{ $hint }}</small>
                    </label>
                </div>
            @endforeach
            <div class="col-12"><small class="text-muted">تقدر تغيّر اختيارك بعدين من لوحة التحكم.</small></div>
        </div>

        <button class="btn btn-primary btn-lg w-100 auth-submit">
            ابدأ التجربة المجانية
            <i class="bi bi-arrow-left-short"></i>
        </button>
    </form>

    <div class="text-center mt-4">
        <a class="text-decoration-none" href="{{ route('login') }}">عندك حساب بالفعل؟ سجّل دخول</a>
    </div>

    <script>
        (function () {
            var form = document.querySelector('[data-register-form]');
            var name = form.querySelector('[data-register-name]');
            var slug = form.querySelector('[data-register-slug]');
            var preview = form.querySelector('[data-register-slug-preview]');
            var touched = slug.value !== '';

            function clean(value) {
                return value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
            }
            function show() { preview.textContent = slug.value || 'your-name'; }

            // Suggest a link from the name while it is Latin; the owner can always type their own.
            name.addEventListener('input', function () {
                if (!touched) { slug.value = clean(name.value); show(); }
            });
            slug.addEventListener('input', function () { touched = true; show(); });
        })();
    </script>
</x-guest-layout>

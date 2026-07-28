<x-guest-layout>
    <div class="mb-4">
        <div class="auth-welcome-icon"><i class="bi bi-person-lock"></i></div>
        <span class="eyebrow text-primary">مرحبًا بعودتك</span>
        <h2 class="auth-title">تسجيل الدخول</h2>
        <p class="text-muted mb-0">أدخل بيانات حسابك للوصول إلى لوحة التحكم.</p>
    </div>

    <x-auth-session-status class="alert alert-success" :status="session('status')" />

    @if ($errors->any())
        <div class="alert alert-danger py-2">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label fw-semibold" for="email">البريد الإلكتروني</label>
            <div class="input-group input-group-lg auth-input">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input id="email" class="form-control" type="email" name="email"
                       value="{{ old('email') }}" placeholder="you@example.com"
                       required autofocus autocomplete="username">
            </div>
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <label class="form-label fw-semibold" for="password">كلمة المرور</label>
                @if (Route::has('password.request'))
                    <a class="small text-decoration-none" href="{{ route('password.request') }}">نسيت كلمة المرور؟</a>
                @endif
            </div>
            <div class="input-group input-group-lg auth-input">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input id="password" class="form-control" type="password" name="password"
                       placeholder="••••••••" required autocomplete="current-password">
                <button class="btn btn-outline-secondary password-toggle" type="button" aria-label="إظهار كلمة المرور">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        </div>

        <div class="form-check mb-4">
            <input id="remember_me" class="form-check-input" type="checkbox" name="remember">
            <label class="form-check-label" for="remember_me">تذكرني على هذا الجهاز</label>
        </div>

        <button class="btn btn-primary btn-lg w-100 auth-submit">
            دخول إلى لوحة التحكم
            <i class="bi bi-arrow-left-short"></i>
        </button>
    </form>

    <div class="auth-divider"><span>دخول آمن ومحمي</span></div>
    <div class="auth-security-row">
        <span><i class="bi bi-shield-lock-fill"></i> اتصال مشفر</span>
        <span><i class="bi bi-fingerprint"></i> حماية الحساب</span>
    </div>
</x-guest-layout>

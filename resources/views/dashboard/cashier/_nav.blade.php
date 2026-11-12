<nav class="subnav" aria-label="أقسام الكاشير">
    <a class="{{ request()->routeIs('dashboard.cashier.index') ? 'active' : '' }}" href="{{ route('dashboard.cashier.index') }}">نقطة البيع</a>
    <a class="{{ request()->routeIs('dashboard.cashier.report') ? 'active' : '' }}" href="{{ route('dashboard.cashier.report') }}">تقرير اليوم</a>
    <a class="{{ request()->routeIs('dashboard.cashier.settings') ? 'active' : '' }}" href="{{ route('dashboard.cashier.settings') }}">الإعدادات</a>
</nav>

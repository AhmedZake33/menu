# Digital Menu SaaS

منصة عربية RTL متعددة المستأجرين لإنشاء منيو إلكتروني للمطاعم والكافيهات. تحتوي على لوحة مستقلة للمدير العام ولوحة لكل مطعم، صفحات منيو متعددة، تصنيفات وأصناف، تخصيص ألوان وتخطيط، روابط عامة، QR Code، إحصائيات مشاهدات، رفع صور، وSoft Delete.

## المتطلبات

- PHP 8.3+ للإنتاج (يعمل Laravel 12 تقنيًا على PHP 8.2)
- MySQL 8+، Composer، Node.js 20+
- امتدادات PHP المعتادة: PDO, Mbstring, OpenSSL, Fileinfo, GD

## التثبيت

```bash
composer install
copy .env.example .env
php artisan key:generate
npm install
```

أنشئ قاعدة MySQL ثم اضبط `.env`:

```dotenv
APP_NAME="Digital Menu SaaS"
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=digital_menu_saas
DB_USERNAME=root
DB_PASSWORD=
```

ثم:

```bash
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

للتطوير استخدم `npm run dev`. يمكن للاختبارات استخدام SQLite in-memory عبر `phpunit.xml`.

## الحسابات التجريبية

| الدور | البريد | كلمة المرور |
|---|---|---|
| Super Admin | `admin@example.com` | `password` |
| Restaurant Admin | `restaurant@example.com` | `password` |

لا يوجد تسجيل عام. ينشئ المدير العام المطاعم وحسابات مديريها.

## الروابط

- `/admin` لوحة المدير العام
- `/dashboard` لوحة المطعم
- `/r/cafe-mocha` المنيو الافتراضي
- `/r/cafe-mocha/menu/main-menu` صفحة محددة
- `/menu/cafe-mocha` الرابط المختصر

## Multi-Tenancy والأمان

تستخدم المنصة قاعدة واحدة وعمود `restaurant_id`. لا يأتي هذا العمود من نماذج مدير المطعم؛ يُؤخذ من المستخدم المصادق عليه داخل الخادم. تتحقق Policies وForm Requests والاستعلامات من الملكية، وتتحقق علاقات التصنيف والصفحة قبل إنشاء الصنف لمنع IDOR وcross-tenant access. الحسابات والمطاعم المعطلة ممنوعة من الدخول، والصفحات العامة لا تعرض السجلات غير النشطة.

الصور تحفظ على قرص `public` بأسماء يولدها Laravel مع تحقق MIME والحجم. الأحجام: الشعار 2MB، الأغلفة 5MB، والتصنيفات والأصناف 3MB. لا يسمح Theme Builder بإدخال CSS أو HTML مخصص.

## تخصيص المنيو وQR

لكل صفحة Theme مستقل: الألوان، grid/list، عدد الأعمدة، الصور، الوصف، الأسعار والبحث. رابط QR يُولد عند الطلب من الرابط الحالي، ولذلك يتغير تلقائيًا مع الـslug، ويدعم:

- `/dashboard/menu-pages/{page}/qr/svg`
- `/dashboard/menu-pages/{page}/qr/png`

## الاختبارات والجودة

```bash
php artisan test
vendor/bin/pint
npm run build
```

تغطي الاختبارات المصادقة، تعطيل التسجيل العام، الملف الشخصي، عزل بيانات المطاعم، منع إسناد تصنيف من مطعم آخر، وإخفاء المطعم المعطل.

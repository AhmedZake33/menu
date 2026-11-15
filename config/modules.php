<?php

/*
| Every screen a client (restaurant) can see is a module. The super admin switches
| modules on or off per client; a module that is off disappears from that client's
| sidebar and its URLs answer 404. "default" is what a client gets when the admin
| has never saved a choice for that module (existing clients keep what they had).
*/

return [
    'list' => [
        'offers' => [
            'label' => 'الخصومات والعروض',
            'description' => 'عروض عامة على أسعار الأصناف مع تحديد مدة العرض.',
            'icon' => 'bi-tags-fill',
            'default' => true,
        ],
        'restaurant_settings' => [
            'label' => 'بيانات المطعم',
            'description' => 'الاسم والشعار وبيانات التواصل والموقع.',
            'icon' => 'bi-building-gear',
            'default' => true,
        ],
        'menu_pages' => [
            'label' => 'صفحات المنيو',
            'description' => 'إنشاء صفحات المنيو وتعديلها.',
            'icon' => 'bi-menu-button-wide',
            'default' => true,
        ],
        'categories' => [
            'label' => 'التصنيفات',
            'description' => 'تصنيفات الأصناف داخل كل صفحة.',
            'icon' => 'bi-tags',
            'default' => true,
        ],
        'items' => [
            'label' => 'الأصناف',
            'description' => 'الأصناف والأسعار والصور.',
            'icon' => 'bi-card-list',
            'default' => true,
        ],
        'files' => [
            'label' => 'الملفات والصور',
            'description' => 'رفع أي ملف: صور الأصناف والتصنيفات والشعار والغلاف. إيقافه يمنع الرفع ويخفي الملفات الموجودة من المنيو دون حذفها.',
            'icon' => 'bi-images',
            'default' => true,
        ],
        'image_menu' => [
            'label' => 'المنيو بالصور',
            'description' => 'خيار رفع صور المنيو الخاصة بالعميل وعرضها على الرابط والـ QR بدل المنيو الرقمي. إيقافه يرجع العميل للمنيو الرقمي.',
            'icon' => 'bi-images',
            'default' => true,
        ],
        'theme' => [
            'label' => 'تصميم المنيو',
            'description' => 'ألوان وتخطيط كل صفحة منيو.',
            'icon' => 'bi-palette',
            'default' => true,
        ],
        'qr' => [
            'label' => 'أكواد QR',
            'description' => 'تحميل QR للمنيو الكامل ولكل صفحة.',
            'icon' => 'bi-qr-code',
            'default' => true,
        ],
        'google_review' => [
            'label' => 'تقييم Google',
            'description' => 'QR ورابط التقييم على Google Maps.',
            'icon' => 'bi-star',
            'default' => true,
        ],
        'public_menu' => [
            'label' => 'المنيو العام',
            'description' => 'رابط المنيو الذي يفتحه العملاء. إيقافه يخفي المنيو عن الزوار.',
            'icon' => 'bi-globe2',
            'default' => true,
        ],
        'orders' => [
            'label' => 'طلبات العملاء',
            'description' => 'استقبال الطلبات من المنيو ومتابعتها.',
            'icon' => 'bi-receipt-cutoff',
            'default' => false,
        ],
        'reservations' => [
            'label' => 'الحجز الأونلاين',
            'description' => 'حجز طاولة من المنيو العام ومتابعة الحجوزات.',
            'icon' => 'bi-calendar-check',
            'default' => false,
        ],
        'cashier' => [
            'label' => 'الكاشير',
            'description' => 'نقطة بيع، تحصيل الطلبات، إيصالات وتقرير يومي.',
            'icon' => 'bi-cash-coin',
            'default' => false,
        ],
    ],

    /*
    | Dashboard colour. "default" is what the admin panel, the login page and any client
    | without a colour of their own use; change it here or with PANEL_BRAND_COLOR in .env.
    | Each client can be given another one from their settings page.
    */
    'brand' => [
        'default' => env('PANEL_BRAND_COLOR', '#a01d48'),
        'presets' => [
            '#a01d48' => 'عنابي',
            '#c2410c' => 'برتقالي',
            '#a16207' => 'ذهبي',
            '#0f7a65' => 'أخضر',
            '#6d28d9' => 'بنفسجي',
            '#334155' => 'رمادي داكن',
        ],
    ],

    // 0 or empty means no limit.
    'limits' => [
        'max_menu_pages' => 'أقصى عدد لصفحات المنيو',
        'max_items' => 'أقصى عدد للأصناف',
        'max_menu_images' => 'أقصى عدد لصور المنيو',
    ],

    /*
    | How a client's public link shows the menu. "digital" is the full menu built in the
    | dashboard; "images" shows pictures the client uploads (a scanned or designed menu).
    | Sections in "digital_only" belong to the digital menu, so they are hidden and their
    | URLs answer 404 while a client is on the image menu. Nothing is deleted by switching.
    */
    'menu_modes' => [
        'digital' => 'المنيو الرقمي',
        'images' => 'منيو بالصور',
    ],
    'digital_only' => ['menu_pages', 'categories', 'items', 'theme', 'offers', 'orders', 'reservations', 'cashier'],

    /*
    | Self-service sign-up (/register). A restaurant that signs up gets a free trial that
    | ends on its own: the subscription date is set to today + trial_days, and once it
    | passes the dashboard and the public menu close exactly as for any expired client.
    | Set SIGNUP_ENABLED=false to close sign-up.
    */
    'signup' => [
        'enabled' => (bool) env('SIGNUP_ENABLED', true),
        'trial_days' => (int) env('SIGNUP_TRIAL_DAYS', 30),
        // Menu pictures a new account may upload. The admin changes it per client afterwards
        // (client settings > حدود الاشتراك); 0 there means unlimited.
        'max_menu_images' => (int) env('SIGNUP_MAX_MENU_IMAGES', 5),
    ],

    'payment_methods' => [
        'cash' => 'نقدي',
        'card' => 'بطاقة',
        'wallet' => 'محفظة إلكترونية',
    ],

    'cashier_defaults' => [
        'tax_percent' => 0,
        'service_percent' => 0,
        'allow_discount' => true,
        'payment_methods' => ['cash', 'card'],
        'receipt_footer' => 'شكرًا لزيارتكم',
    ],
];

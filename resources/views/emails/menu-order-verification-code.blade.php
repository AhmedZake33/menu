<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        body{font-family:Tahoma,Arial,sans-serif;background:#f6f5fa;color:#211d2c;margin:0;padding:24px}
        .card{max-width:560px;margin:auto;background:#fff;border-radius:16px;padding:24px;border:1px solid #eee;text-align:center}
        .code{display:inline-block;margin:18px 0;padding:14px 24px;border-radius:14px;background:#17131f;color:#fff;font-size:32px;font-weight:900;letter-spacing:8px;direction:ltr}
        p{line-height:1.8}
    </style>
</head>
<body>
    <div class="card">
        <h1>تأكيد طلبك</h1>
        <p>استخدم الكود التالي لتأكيد طلبك من {{ $restaurant->name }}.</p>
        <div class="code">{{ $code }}</div>
        <p>الكود صالح لمدة {{ $expiresInMinutes }} دقائق فقط.</p>
        <p>لو أنت لم تطلب أوردر، تجاهل هذه الرسالة.</p>
    </div>
</body>
</html>

# مركز القيادة

منظومة عربية لإدارة المهام والجدولة وتتبع الوقت لمدير المكتب ومدير الحسابات ومساعد المدير، بتسجيل دخول حقيقي (بريد + كلمة مرور) ومزامنة على كل الأجهزة.

- **الواجهة:** لوحة قيادة، «يومي»، مهام بفلاتر وبحث، كانبان، تقويم (ميلادي + هجري)، قوالب دورية، تتبع وقت ودوام، تقارير، تصدير JSON/CSV.
- **الخادم:** Laravel 13 + PostgreSQL. لكل مستخدم مستند خاص به، ولا يصل أحد إلى بيانات غيره.
- **الحسابات:** لا يوجد تسجيل ذاتي. المدير يضيف المستخدمين من صفحة `/users`، وكل مستخدم يغيّر كلمته من `/account`.

## التشغيل المحلي (للمطوّر)
```bash
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate
ADMIN_EMAIL=you@example.com ADMIN_PASSWORD='كلمة-مرور-10-أحرف-فأكثر' php artisan markaz:bootstrap-admin
php artisan serve
php artisan test
```

## النشر على Laravel Cloud
1. أنشئ تطبيقًا من هذا المستودع (الفرع الحالي)، المنطقة: الشرق الأوسط.
2. أضف قاعدة **Postgres** واربطها بالتطبيق (تُحقن بيانات الاتصال تلقائيًا).
3. متغيرات البيئة:
   ```
   APP_NAME="مركز القيادة"
   APP_LOCALE=ar
   APP_TIMEZONE=Asia/Riyadh
   SESSION_SECURE_COOKIE=true
   ADMIN_EMAIL=بريدك
   ADMIN_PASSWORD=كلمة مرور قوية (10 أحرف فأكثر)
   ADMIN_NAME=اسمك
   ```
4. أوامر النشر (Deploy commands): `php artisan migrate --force` ثم `php artisan markaz:bootstrap-admin`
   (الثاني ينشئ حساب المدير في أول نشر فقط. لو نسيت كلمة المرور: أضف `ADMIN_RESET=true` وأعد النشر ثم احذفه.)
5. أوامر البناء: `composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader`

## ملاحظات
- النسخة المحلية في المتصفح تعمل أيضًا دون اتصال وتُزامَن عند عودته؛ الأحدث يفوز عند التعارض بين جهازين.
- مواعيد قوالب الضريبة والتأمينات والزكاة اقتراحية؛ تحقق من المواعيد الرسمية.

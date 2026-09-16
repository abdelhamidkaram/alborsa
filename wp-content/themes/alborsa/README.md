# قالب alborsa

قالب WordPress مخصص مستقل للعرض فقط. يطبق اتجاه **Editorial Signal** المعتمد، وهو RTL أولاً ومتجاوب ويستهدف WCAG 2.2 AA. تبقى قواعد المنتج والبيانات والصلاحيات والمزودات داخل إضافة `alborsa-platform`.

## القوالب المنفذة

- `front-page.php`: الرئيسية بالموجز والأخبار والأسواق والتحليل والوسطاء والتعليم والنشرة.
- `home.php` و`page-akhabar.php`: فهرس الأخبار.
- `archive.php`: أرشيفا التحليل والتعليم وبقية أرشيفات WordPress.
- `page-aswaq.php`: الأسواق وسجل الأصول المنشورة وحالات البيانات.
- `archive-alb_broker.php` و`page-brokers.php`: دليل الوسطاء واختيار من وسيطين إلى ثلاثة للمقارنة.
- `single-alb_broker.php`: صفحة الوسيط وبياناته وتراخيصه العامة الموثقة بالمصدر.
- `page-adawat.php`: الحاسبات الأربع العاملة وحالة الأدوات المحجوبة أو المؤجلة.
- `single.php`: الأخبار والتحليلات والأدلة المفردة.
- `template-parts/content/asset-profile.php`: صفحة الأصل المالي مع آخر لقطة سعرية متاحة، وتُستدعى من `single.php` للأصل `alb_asset`.
- `search.php` و`404.php` و`page.php`: البحث وعدم العثور والصفحات العامة.

## الإعداد على WordPress

1. فعّل إضافة `alborsa-platform` ثم القالب `alborsa`.
2. أنشئ صفحات بالمسارات `aswaq` و`akhabar` و`adawat` عند الحاجة، واجعل `akhabar` صفحة المقالات أو اترك قالب الصفحة البديل يعمل. يوفر نوع الوسيط أرشيف `/brokers/` بنفسه، ولا يحتاج صفحة مستقلة عند تفعيل الإضافة.
3. عيّن القوائم `primary` و`utility` و`footer`. يعرض القالب قائمة احتياطية إذا لم تُعيّن القائمة الرئيسية أو قائمة التذييل.
4. راجع إعداد Polylang بحيث تبقى العربية على `/` والإنجليزية على `/en/` طبقاً للقرار D-002.
5. اختبر Search appearance وCanonical وSchema مع Yoast، ثم اختبر Breeze وObject Cache Pro على Staging.

## حدود النسخة الحالية

- لا يجلب القالب أي API خارجي أثناء تحميل الصفحة.
- لا يقرأ جدول الأسعار أو خريطة المزودين المحمية، ولا يصف البيانات بأنها لحظية.
- يقرأ آخر لقطة سعرية عبر REST بعد تحميل الصفحة حتى لا تُحتجز في كاش الصفحة؛ تبقى الحالة غير متاحة حتى يضاف مزود معتمد وتُشغّل المزامنة.
- لا يعرض توصية وسيط أو رابط عمولة قبل اعتماد منهجية التقييم وسياسة التحقق والأفلييت.
- الحاسبات الأربع متاحة وتعمل بخدمات Stateless؛ الزكاة وتحويل العملات مغلقان حتى اعتماد المنهجية والمزود.
- Visual QA الحي وإعداد الصفحات والقوائم والمحتوى على Staging ما زالت مهام تشغيلية مطلوبة.

## الجودة المحلية

من جذر المستودع:

```bash
find wp-content/themes/alborsa -name '*.php' -print0 | xargs -0 -n1 php -l
wp-content/plugins/alborsa-platform/vendor/bin/phpcs --standard=WordPress --extensions=php --ignore='*/vendor/*' wp-content/themes/alborsa
node --check wp-content/themes/alborsa/assets/js/navigation.js
node --check wp-content/themes/alborsa/assets/js/platform.js
```

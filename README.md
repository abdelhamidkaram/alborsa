# alborsa.net — التخطيط التقني

هذا المستودع مخصص لبناء منصة `alborsa.net` على WordPress. بدأ تنفيذ طبقة Foundation للإضافة، بينما تظل وحدات المنتج والتصميم عقوداً مخططة لم تبدأ بعد.

> الحالة في 2026-09-16: نُفذت Foundation مع migration الإصدار 2 لجدول العلاقات `{$wpdb->prefix}alb_relationships` تحت `wp-content/plugins/alborsa-platform`. لا توجد REST endpoints أو أنواع محتوى أو جداول مزايا أخرى أو واجهات Frontend حتى الآن. تبقى أسماء وحدات المنتج وعقودها الواردة في الوثائق مقترحات إلى أن تبدأ مراحلها.

## المنفذ حالياً

- Plugin bootstrap مع Composer وPSR-4.
- الحد الأدنى المعلن للبناء: WordPress `6.6` وPHP `8.1`.
- Module Registry صغير، ووحدة `Foundation` فقط مسجلة حالياً.
- Activation/deactivation متكرران بأمان، مع دعم التفعيل على Multisite وعدم حذف بيانات دائمة عند التعطيل.
- نظام Migrations بإصدار محفوظ في Option: الإصدار `1` يهيئ صلاحية الإدارة، والإصدار `2` ينشئ جدول العلاقات `{$wpdb->prefix}alb_relationships` بشكل idempotent عبر `dbDelta` وبدون Foreign Keys، مع مفتاح فريد لمنع التكرار وفهرس للبحث العكسي.
- Capability واحدة محافظة باسم `manage_alborsa_platform` تضاف لدور `administrator`.
- فحوص Availability غير ملزمة للإضافات السبع الموجودة، بدون استدعاء APIs تابعة لها وبدون Fatal errors عند غيابها.
- فحصان داخل Site Health وأمر `wp alborsa health`.
- اختبارات PHPUnit معزولة وPHPCS وPHPStan وCI. لم يُعتمد بعد Harness تكامل WordPress حي داخل المستودع.

## أمر الفحص السريع

شغّل الأمر التالي من داخل تثبيت WordPress لفحص Migration والتكاملات المطلوبة:

```bash
wp alborsa health
```

يعرض الأمر حالة Migration والتكاملات، ويرجع خطأ إذا كانت Migration غير مكتملة أو كان تكامل إنتاج مطلوب غير متاح.

## القرار المعماري الأساسي

- منطق المنتج يوضع في إضافة مستقلة باسم مبدئي `alborsa-platform`.
- القالب مسؤول عن العرض والتنسيق فقط، ويمكن تنفيذه بعد استلام التصميم.
- بيانات السوق تدخل عبر طبقة مزودين قابلة للاستبدال، ولا تُطلب مباشرة أثناء تحميل الصفحة.
- الإصدار الأول عربي، بدون حسابات مستخدمين أو قوائم مراقبة أو تنبيهات أسعار.
- لا يُستخدم وصف «لحظي» لأي بيانات قبل تأكيد الترخيص ودرجة التأخير من مزود البيانات.

## وثائق المشروع

- [Foundation handoff](docs/00-foundation-handoff.md)
- [معمارية الإضافة](docs/01-plugin-architecture.md)
- [نموذج البيانات](docs/02-data-model.md)
- [خطة التنفيذ والـ Backlog](docs/03-implementation-plan.md)
- [القرارات المفتوحة](docs/04-open-decisions.md)
- [إضافات WordPress الموجودة ونقاط التكامل](docs/05-existing-wordpress-stack.md)
- [سجل تقدم التنفيذ](docs/06-implementation-progress.md)

## إضافات WordPress الموجودة

بحسب لقطة لوحة التحكم بتاريخ 2026-09-15، الموقع يستخدم حالياً:

- Antispam Bee `2.11.13`
- Breeze `2.5.16`
- Object Cache Pro `1.25.5`
- Polylang `3.8.9`
- WP 2FA `4.1.0`
- Yoast SEO `28.4`
- WPS Hide Login `1.9.19`

الإصدارات أعلاه Snapshot من الصورة وليست فحصاً مباشراً للخادم، ويجب إعادة التحقق منها عند بدء التنفيذ أو قبل أي تحديث.

## حدود مرحلة Foundation وبداية Phase 2

المنفذ داخل النطاق:

- تشغيل الإضافة، تسجيل الوحدات، Versioned migrations والصلاحيات الأساسية.
- Migration الإصدار `2` لإنشاء جدول العلاقات `{$wpdb->prefix}alb_relationships` عبر `dbDelta`.
- تشخيص Availability للتكاملات الحالية فقط؛ لا تُفحص الإصدارات والتراخيص والإعدادات تلقائياً.
- Site Health وWP-CLI health command وبوابات الجودة.

خارج النطاق مؤقتاً:

- وحدات المنتج التحريرية والتجارية: Editorial وAssets وMarket Data وBrokers وAffiliate وFinancial Tools وSEO وMultilingual integration.
- أنواع المحتوى والتصنيفات وخدمات العلاقات وREST endpoints والجداول المخصصة الأخرى (`alb_market_quotes`, `alb_affiliate_links`, `alb_affiliate_clicks`).
- مزودو البيانات والحسابات وWatchlists وAlerts.
- الهوية البصرية.
- القالب وملفات CSS.
- تفاصيل شكل المكونات وترتيبها داخل الصفحات.
- Screener والاشتراكات المدفوعة.

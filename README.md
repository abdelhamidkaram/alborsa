# alborsa.net — التخطيط التقني

هذا المستودع مخصص لبناء منصة `alborsa.net` على WordPress. نُفذت طبقة Foundation وبدأت وحدتا Editorial وAssets، بينما تظل بقية وحدات المنتج والتصميم عقوداً مخططة أو قيد التنفيذ.

> الحالة في 2026-09-16: اكتملت الأجزاء الخلفية المعتمدة من المرحلة الثانية للأصول والمحتوى، وبدأت المرحلة الثالثة بعقد أسعار محايد وDTO مطبع وMock Provider فقط. مزود السوق الحقيقي ما زال محجوباً بالقرار D-001، ولا توجد اتصالات سوق خارجية أو واجهات Frontend حتى الآن.

## المنفذ حالياً

- Plugin bootstrap مع Composer وPSR-4.
- الحد الأدنى المعلن للبناء: WordPress `6.6` وPHP `8.1`.
- Module Registry صغير، ووحدات `Foundation` و`Editorial` و`Assets` مسجلة حالياً.
- Activation/deactivation متكرران بأمان، مع دعم التفعيل على Multisite وعدم حذف بيانات دائمة عند التعطيل.
- نظام Migrations بإصدار محفوظ في Option: الإصدار `1` يهيئ صلاحية الإدارة، والإصدار `2` ينشئ جدول العلاقات `{$wpdb->prefix}alb_relationships` بشكل idempotent عبر `dbDelta` وبدون Foreign Keys، مع مفتاح فريد لمنع التكرار وفهرس للبحث العكسي.
- Migration الإصدار `3` يثبت صلاحيات محتوى مستقلة ومشتركة للأنواع المنفذة، ويمنحها لدوري `administrator` و`editor` مع إبقاء `manage_alborsa_platform` للإدارة فقط.
- `RelationshipRepository` يتحقق من المعرفات والمفاتيح قبل الإدخال، يكتب وقت UTC، ويقرأ بحسب المصدر أو الهدف بترتيب ثابت وحد أقصى 100 علاقة؛ لا توجد واجهة كتابة عامة أو Cache.
- `ContentAssetRelationshipService` يفرض أن المصدر خبر أو تحليل أو دليل وأن الهدف أصل، ويدعم حالياً `mentions` و`analyzes` للربط والحذف والقراءة الاتجاهية؛ لا توجد واجهة كتابة عامة بعد.
- نوعا المحتوى العامان `alb_analysis` و`alb_guide` مع دعم محرر المكونات وREST القياسي والمراجعات، وبنيتي روابط `/tahleel/` و`/taleem/` المعتمدتين.
- سجل `alb_asset` مع تصنيفات النوع والسوق والقطاع، ومساري `/aswaq/tadawul/{symbol}/` و`/forex/{pair}/` المقيدين بتصنيف الأصل.
- حقول الأصل العامة للرمز والعملات وISIN والموقع والحالة، مع خريطة مزودين داخلية محمية وغير مكشوفة في REST القياسي.
- مسارا REST للقراءة فقط: `GET /wp-json/alborsa/v1/assets` للقائمة المنشورة مع البحث وفلاتر النوع والسوق، و`GET /wp-json/alborsa/v1/assets/{id}` للتفاصيل والمحتوى المنشور المرتبط. القائمة محدودة بـ100 عنصراً والصفحة بـ1000، ولا تكشف خريطة المزودين المحمية.
- أمر `wp alborsa assets import <file> [--dry-run]` لاستيراد CSV حتى 5MB و5000 صف. يطابق الأصل بواسطة اللغة والرمز القانوني، ويتحقق من لغة Polylang والتصنيفات الموجودة، ويحافظ على الحقول الاختيارية غير الموجودة عند التحديث.
- `QuoteProviderInterface` و`NormalizedQuote` و`MockQuoteProvider` كحد محايد لبيانات السوق: الأرقام العشرية Strings دقيقة، والتوقيت يتحول إلى UTC، والدفعات محدودة إلى 100 رمز في المزود الوهمي. لا يوجد Adapter حقيقي أو استدعاء خارجي.
- تصنيفات الموضوع ومستوى الدليل ومساره، مع تعطيل أرشيفات التصنيفات العامة لحين اعتماد عقود روابطها.
- `wpml-config.xml` يثبت أنواع المحتوى والتصنيفات القابلة للترجمة، ويزامن حقول الأصل المحايدة فقط بين الترجمات.
- إصدار مستقل لقواعد إعادة الكتابة ينفذ Soft Flush مرة واحدة بعد تسجيل الوحدات، ولا ينفذ Flush في كل طلب.
- صلاحية الإدارة `manage_alborsa_platform` خاصة بالمدير، مع مجموعة `alb_content` للتحرير والنشر والمراجعة والحذف لدى المدير والمحرر.
- فحوص Availability غير ملزمة للإضافات السبع الموجودة، بدون استدعاء APIs تابعة لها وبدون Fatal errors عند غيابها.
- فحصان داخل Site Health وأمر `wp alborsa health`.
- اختبارات PHPUnit معزولة وPHPCS وPHPStan وCI. لم يُعتمد بعد Harness تكامل WordPress حي داخل المستودع.

## أمر الفحص السريع

شغّل الأمر التالي من داخل تثبيت WordPress لفحص Migration والتكاملات المطلوبة:

```bash
wp alborsa health
```

يعرض الأمر حالة Migration والتكاملات، ويرجع خطأ إذا كانت Migration غير مكتملة أو كان تكامل إنتاج مطلوب غير متاح.

## استيراد الأصول

نفّذ Dry-run أولاً:

```bash
wp alborsa assets import /path/to/assets.csv --dry-run
wp alborsa assets import /path/to/assets.csv
```

الأعمدة الإلزامية هي `language,title,canonical_symbol,asset_type`. الأعمدة الاختيارية هي `slug,post_status,excerpt,content,exchange,sector,base_currency,quote_currency,isin,official_website,listing_status`. يجب أن تكون Terms الخاصة بالنوع والسوق والقطاع موجودة مسبقاً ومربوطة في Polylang بنفس لغة الصف. القيم المسموحة للحالة هي `draft,pending,publish,private`، ويمنع المستورد نشر الإنجليزية حالياً.

```csv
language,title,canonical_symbol,asset_type,exchange,base_currency,listing_status
ar,أرامكو السعودية,2222,stock,tadawul,SAR,active
```

هوية المطابقة هي `(language, canonical_symbol)`. الأعمدة الاختيارية غير الموجودة — وكذلك `post_status` أو `slug` الفارغان — لا تستبدل القيمة الحالية عند التحديث؛ أما الأصل الجديد فينشأ `draft` افتراضياً.

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
- Migration الإصدار `3` لإضافة قدرات المحتوى إلى المدير والمحرر.
- تشخيص Availability للتكاملات الحالية فقط؛ لا تُفحص الإصدارات والتراخيص والإعدادات تلقائياً.
- Site Health وWP-CLI health command وبوابات الجودة.

خارج النطاق مؤقتاً:

- وحدات المنتج المتبقية: Market Data وBrokers وAffiliate وFinancial Tools وSEO وMultilingual integration، والأجزاء التحريرية والأصولية غير المنفذة بعد.
- أنواع المحتوى المتبقية والتصنيفات ومسارات REST غير الخاصة بقراءة الأصول والجداول الأخرى (`alb_market_quotes`, `alb_affiliate_links`, `alb_affiliate_clicks`)؛ لا توجد واجهة REST عامة لكتابة العلاقات.
- مزودو البيانات والحسابات وWatchlists وAlerts.
- الهوية البصرية.
- القالب وملفات CSS.
- تفاصيل شكل المكونات وترتيبها داخل الصفحات.
- Screener والاشتراكات المدفوعة.

# alborsa.net — التخطيط التقني

هذا المستودع مخصص لبناء منصة `alborsa.net` على WordPress. نُفذت طبقة Foundation ووحدات Editorial وAssets وMarketData وBrokers وFinancialTools، كما نُفذ القالب المخصص الأول، بينما تظل الوحدات المحجوبة بقرارات مفتوحة أو التي تحتاج بيئة تشغيل حية قيد التنفيذ.

> الحالة في 2026-09-16: اكتملت الأجزاء الخلفية المعتمدة من المرحلة الثانية، واكتملت البنية التشغيلية المحايدة للمرحلة الثالثة: مزامنة دفعية من WP-CLI، أحدث Snapshot، Object Cache موجّه، REST للقراءة، stale fallback، وفحص صحة. نُفذ أيضاً قالب RTL متجاوب. مزود السوق الحقيقي ما زال محجوباً بالقرار D-001، لذلك لا توجد اتصالات سوق خارجية أو ادعاءات ببيانات لحظية.

## المنفذ حالياً

- Plugin bootstrap مع Composer وPSR-4.
- الحد الأدنى المعلن للبناء: WordPress `6.6` وPHP `8.1`.
- Module Registry صغير، ووحدات `Foundation` و`Editorial` و`Assets` و`MarketData` و`Brokers` و`FinancialTools` مسجلة حالياً.
- Activation/deactivation متكرران بأمان، مع دعم التفعيل على Multisite وعدم حذف بيانات دائمة عند التعطيل.
- نظام Migrations بإصدار محفوظ في Option: الإصدار `1` يهيئ صلاحية الإدارة، والإصدار `2` ينشئ جدول العلاقات `{$wpdb->prefix}alb_relationships` بشكل idempotent عبر `dbDelta` وبدون Foreign Keys، مع مفتاح فريد لمنع التكرار وفهرس للبحث العكسي.
- Migration الإصدار `3` يثبت صلاحيات محتوى مستقلة ومشتركة للأنواع المنفذة، ويمنحها لدوري `administrator` و`editor` مع إبقاء `manage_alborsa_platform` للإدارة فقط.
- Migration الإصدار `4` ينشئ `{$wpdb->prefix}alb_market_quotes` لأحدث Snapshot لكل أصل ومزود، مع `QuoteRepository` للـupsert والقراءة الدقيقة وبدون تخزين Price history.
- `RelationshipRepository` يتحقق من المعرفات والمفاتيح قبل الإدخال، يكتب وقت UTC، ويقرأ بحسب المصدر أو الهدف بترتيب ثابت وحد أقصى 100 علاقة؛ لا توجد واجهة كتابة عامة أو Cache.
- `ContentAssetRelationshipService` يفرض أن المصدر خبر أو تحليل أو دليل وأن الهدف أصل، ويدعم حالياً `mentions` و`analyzes` للربط والحذف والقراءة الاتجاهية؛ لا توجد واجهة كتابة عامة بعد.
- نوعا المحتوى العامان `alb_analysis` و`alb_guide` مع دعم محرر المكونات وREST القياسي والمراجعات، وبنيتي روابط `/tahleel/` و`/taleem/` المعتمدتين.
- سجل `alb_asset` مع تصنيفات النوع والسوق والقطاع، ومساري `/aswaq/tadawul/{symbol}/` و`/forex/{pair}/` المقيدين بتصنيف الأصل.
- حقول الأصل العامة للرمز والعملات وISIN والموقع والحالة، مع خريطة مزودين داخلية محمية وغير مكشوفة في REST القياسي.
- مسارا REST للقراءة فقط: `GET /wp-json/alborsa/v1/assets` للقائمة المنشورة مع البحث وفلاتر النوع والسوق، و`GET /wp-json/alborsa/v1/assets/{id}` للتفاصيل والمحتوى المنشور المرتبط. القائمة محدودة بـ100 عنصراً والصفحة بـ1000، ولا تكشف خريطة المزودين المحمية.
- أمر `wp alborsa assets import <file> [--dry-run]` لاستيراد CSV حتى 5MB و5000 صف. يطابق الأصل بواسطة اللغة والرمز القانوني، ويتحقق من لغة Polylang والتصنيفات الموجودة، ويحافظ على الحقول الاختيارية غير الموجودة عند التحديث.
- `QuoteProviderInterface` و`NormalizedQuote` و`MockQuoteProvider` كحد محايد لبيانات السوق: الأرقام العشرية Strings دقيقة، والتوقيت يتحول إلى UTC، والدفعات محدودة إلى 100 رمز في المزود الوهمي. لا يوجد Adapter حقيقي أو استدعاء خارجي.
- `MarketDataModule` يربط خرائط الأصول المحمية بالمزوّدين المسجلين عبر Filter باسم `alborsa_platform_quote_providers`. لا يسجل المشروع مزوّداً تجارياً افتراضياً قبل اعتماد D-001.
- أمر `wp alborsa market sync --provider=<key>` يمر على كل الأصول المنشورة في دفعات حتى 100 أصل، يطلب كل رمز فريد مرة واحدة في الدفعة، ويحفظ أحدث Snapshot فقط.
- `QuoteCache` يستخدم WordPress Object Cache group باسم `alborsa_platform_quotes` ومفاتيح موجهة لكل أصل/مزود، بدون Full Flush أو Page Cache purge.
- `QuoteReadService` و`GET /wp-json/alborsa/v1/assets/{id}/quote` يعيدان السعر المنشور مع `source` و`as_of` و`delay_seconds` و`status` و`stale`، ولا يكشفان مفتاح المزود أو رمزه الداخلي. عند فشل المزوّد بعد آخر مزامنة تبقى آخر قيمة سليمة مع `status=stale`.
- سجل `alb_broker` على `/brokers/{slug}/` وأرشيف `/brokers/` مع تصنيفي `alb_broker_type` و`alb_jurisdiction` وحقول منظمة للخدمات والمنصات والعملات والإيداع والحسابات والدعم العربي.
- شاشة إدارة داخل محرر الوسيط للملف والتراخيص. لا تظهر رخصة عامة إلا إذا كانت حالتها `verified` وبها جهة ورقم ومصدر رسمي وتاريخ تحقق؛ الملاحظات الداخلية لا تخرج في REST.
- مسارات قراءة الوسطاء: `GET /wp-json/alborsa/v1/brokers` و`GET /wp-json/alborsa/v1/brokers/{id}` و`GET /wp-json/alborsa/v1/brokers/compare?ids=1,2`، مع حد مقارنة من وسيطين إلى ثلاثة.
- مراجعات الوسطاء مبنية فوق WordPress Comments بنوع `alb_broker_review` وMeta محمية ذات بادئة. يعرض الجمهور التعليقات المقبولة فقط، وتستبعد حالة `rejected` من القائمة والمتوسط. تبقى شارة Verified مغلقة وواجهة POST العامة غير مسجلة حتى اعتماد D-003 وD-013.
- التقييم التحريري منفصل عن متوسط المستخدمين ويعيد `methodology_pending` بدون رقم حتى اعتماد D-004. عروض الأفلييت تعيد `available=false` حتى تنفيذ D-005.
- سجل أدوات مالي يقدّم أربع حاسبات Stateless: الربح والخسارة، حجم الصفقة، قيمة النقطة، ونمو الاستثمار المركب. المدخلات الرقمية محدودة وتُرسل كسلاسل عشرية، ولا تُحفظ مدخلات الزائر.
- `GET /wp-json/alborsa/v1/tools` يعرض الكتالوج وحالته، و`POST /wp-json/alborsa/v1/tools/{slug}/calculate` يعيد نتيجة تقديرية مع الافتراضات وإخلاء المسؤولية. تبقى حاسبة الزكاة بحالة `methodology_pending` وفق D-006، ومحول العملات بحالة `provider_pending` وفق D-008.
- تصنيفات الموضوع ومستوى الدليل ومساره، مع تعطيل أرشيفات التصنيفات العامة لحين اعتماد عقود روابطها.
- `wpml-config.xml` يثبت أنواع المحتوى والتصنيفات القابلة للترجمة، ويزامن حقول الأصل المحايدة فقط بين الترجمات.
- إصدار مستقل لقواعد إعادة الكتابة ينفذ Soft Flush مرة واحدة بعد تسجيل الوحدات، ولا ينفذ Flush في كل طلب.
- صلاحية الإدارة `manage_alborsa_platform` خاصة بالمدير، مع مجموعة `alb_content` للتحرير والنشر والمراجعة والحذف لدى المدير والمحرر.
- فحوص Availability غير ملزمة للإضافات السبع الموجودة، بدون استدعاء APIs تابعة لها وبدون Fatal errors عند غيابها.
- ثلاثة فحوص داخل Site Health، منها حالة تهيئة ومزامنة مزوّدي السوق، إضافة إلى أمر Foundation: `wp alborsa health`.
- اختبارات PHPUnit معزولة وPHPCS وPHPStan وCI. لم يُعتمد بعد Harness تكامل WordPress حي داخل المستودع.
- قالب WordPress مستقل باسم `alborsa` باتجاه Editorial Signal المعتمد، مع نظام تصميم موثق في `DESIGN.md` و`PRODUCT.md`.
- الترويسة والتنقل وشريط حالة السوق والتذييل، مع Mobile dock وبحث متوافق مع WordPress.
- الصفحة الرئيسية بموجز اليوم وآخر خمسة أخبار والأسواق والأصول الأكثر تداولاً والتحليل والوسيط المميز والتعليم والنشرة؛ العناصر التي تعتمد على مصدر أو سياسة غير معتمدة تعرض حالة عدم توفر واضحة.
- قوالب الأسواق والأخبار والتحليل والتعليم والوسطاء والأدوات وصفحات المحتوى والأصل والبحث و404. لا يقرأ القالب جدول الأسعار ولا خريطة المزودين المحمية.

## أمر الفحص السريع

شغّل الأمر التالي من داخل تثبيت WordPress لفحص Migration والتكاملات المطلوبة:

```bash
wp alborsa health
```

يعرض الأمر حالة Migration والتكاملات، ويرجع خطأ إذا كانت Migration غير مكتملة أو كان تكامل إنتاج مطلوب غير متاح.

## مزامنة أسعار السوق

بعد تركيب Adapter معتمد عبر `alborsa_platform_quote_providers` وإضافة خرائط `quotes` المفعلة للأصول، يشغل Server Cron الأمر التالي خارج دورة طلبات الواجهة:

```bash
wp alborsa market sync --provider=provider-key
```

يفشل الأمر صراحة إذا لم يكن المزوّد مسجلاً أو لم توجد خرائط مفعلة أو أعاد المزوّد دفعة ناقصة. لا يستخدم `MockQuoteProvider` تلقائياً في الإنتاج، ولا ينفذ المسار العام أي اتصال خارجي.

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
- القالب المخصص `alborsa` مسؤول عن العرض والتنسيق فقط، وتظل قواعد المنتج والبيانات داخل الإضافة.
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
- Migration الإصدار `4` لإنشاء جدول أحدث الأسعار `{$wpdb->prefix}alb_market_quotes`.
- تشخيص Availability للتكاملات الحالية فقط؛ لا تُفحص الإصدارات والتراخيص والإعدادات تلقائياً.
- Site Health وWP-CLI health command وبوابات الجودة.

خارج النطاق مؤقتاً:

- وحدات المنتج المتبقية: Affiliate وSEO والتكاملات التحريرية/التشغيلية غير المنفذة بعد، إضافة إلى الأجزاء المحجوبة من الوسطاء وبيانات السوق والأدوات بقرارات مفتوحة.
- أنواع المحتوى المتبقية والتصنيفات ومسارات REST غير الخاصة بالأصول والأسعار والجداول الأخرى (`alb_affiliate_links`, `alb_affiliate_clicks`)؛ لا توجد واجهة REST عامة لكتابة العلاقات.
- Adapter سوق حقيقي وترخيصه، ومنهجية تقييم الوسطاء والتحقق العام من المراجعات وحماية نموذجها، والحسابات وWatchlists وAlerts.
- تشغيل القالب على Staging، إدخال المحتوى الحقيقي، وضبط الصفحات والقوائم وإعدادات Polylang وYoast والكاش.
- Screener والاشتراكات المدفوعة.

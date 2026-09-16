# إضافات WordPress الموجودة

> الحالة: Snapshot من لقطة لوحة تحكم قدمها مالك المشروع بتاريخ 2026-09-15. لم يتم بعد فحص الخادم أو إعدادات كل إضافة أو ملفات Drop-ins، لذلك «نشطة» لا تعني «مضبوطة كما نحتاج».

## 1. الجرد الحالي

ظهور رابط «تعطيل» أمام كل إضافة في الصورة هو دليل الحالة النشطة وقت التقاطها.

| الإضافة | الإصدار الظاهر | الدور في المعمارية | ما يجب التحقق منه |
|---|---:|---|---|
| Antispam Bee | `2.11.13` | فلترة Spam لتعليقات WordPress | توافقه مع نوع مراجعات الوسطاء وطريقة إرسالها |
| Breeze | `2.5.16` | Page cache وتحسينات Frontend وإدارة Purge | إعدادات Cache وVarnish والاستثناءات وعدم كسر Widgets |
| Object Cache Pro | `1.25.5` | Persistent object cache | صحة الاتصال، TTLs، حدود الذاكرة، وعدم استخدام مزايا 2.x |
| Polylang | `3.8.9` | اللغات والترجمات وبنية Language URLs | مطابقة العربية على `/` والإنجليزية على `/en/`، وCPTs وTaxonomies وMeta sync |
| WP 2FA | `4.1.0` | حماية حسابات فريق WordPress | هل 2FA إلزامي فعلاً، ولأي أدوار، وخطة recovery |
| Yoast SEO | `28.4` | Canonical، robots، sitemap، metadata وSchema graph | إعداد Search appearance وتكامل أنواع المحتوى المخصصة |
| WPS Hide Login | `1.9.19` | تغيير مسار صفحة تسجيل الدخول | المسار الحالي، توثيقه بأمان، واختبار الاستعادة |

تظهر الصورة أيضاً أن التحديثات التلقائية معطلة للإضافات السبع. هذا لا يعني وجوب تشغيلها مباشرة؛ يلزم تحديد سياسة تحديث واختبار ونسخ احتياطي تناسب بيئة الإنتاج.

تظهر روابط ترقية إلى Premium في صفوف بعض الإضافات، لكن الصورة وحدها لا تكفي لبناء Compatibility matrix دقيقة للنسخ المجانية/المدفوعة. لا تعتمد الخطة على ميزة مدفوعة قبل فحص الترخيص والإعداد على الخادم.

## 2. قرارات الدمج

كل الإضافات التالية Soft integrations داخل الكود: لا تستدعي `alborsa-platform` دالة أو Class تابعة لها قبل التحقق من توفرها. Polylang وYoast مطلوبان لتجهيز الإنتاج، لكن تعطيل أي منهما في بيئة التطوير لا يجب أن يسبب Fatal error.

منذ Foundation المنفذة في 2026-09-16، يظهر Availability للإضافات السبع في Site Health وعبر `wp alborsa health`. يعتمد الفحص على ملف الإضافة النشط أو Runtime marker معروف، ولا يثبت الإصدار أو Edition أو الترخيص أو الإعداد أو صحة الخدمة؛ تظل أعمال الـAudit أدناه مطلوبة.

### Polylang بدلاً من WPML

- تعتمد الإضافة المخصصة على Polylang في اللغة والترجمة.
- نسجل Custom Post Types وTaxonomies العامة بطريقة متوافقة مع WordPress حتى يستطيع Polylang إدارتها.
- الحقول المحايدة، مثل رمز المزود وISIN، تنسخ بين الترجمات.
- الحقول التحريرية، مثل الوصف والمزايا والعيوب، تترجم بشكل مستقل.
- يسمح Polylang باستخدام `wpml-config.xml` لتحديد سياسة الحقول؛ اسم الملف لا يعني أن WPML مثبت.
- أضيف `wpml-config.xml` في جذر `alborsa-platform`: الأنواع والتصنيفات المنفذة قابلة للترجمة، وحقول هوية الأصل وخريطة المزودين تستخدم `copy`. يلزم اختبار تحميل الملف ومزامنته فعلياً على Staging.
- اعتمد القرار D-002 العربية على `/` من دون بادئة والإنجليزية تحت `/en/`؛ يلزم فحص URL modification settings للتأكد من مطابقتها على Staging.

مراجع Polylang الرسمية:

- [URL modifications](https://polylang.pro/documentation/support/guides/url-modifications/)
- [Multilingual Custom Post Types and Taxonomies](https://polylang.pro/documentation/support/guides/multilingual-custom-post-types-and-taxonomies/)
- [Synchronize metadatas between translations](https://polylang.pro/documentation/support/guides/synchronize-metadatas-between-translations/)

### Yoast SEO بدلاً من Rank Math

- لا تطبع إضافة `alborsa-platform` Canonical أو Sitemap أو Graph أساسي خاصاً بها.
- الكيانات الخاصة بالأصل أو الوسيط تضاف إلى Graph الخاص بـYoast من خلال Schema API الرسمية.
- كل نوع محتوى جديد يراجع في Search appearance قبل إطلاقه.
- نختبر عدم وجود Schema nodes مكررة على صفحة الأصل والوسيط والمقارنة.

مرجع Yoast الرسمي: [Schema integration guidelines](https://developer.yoast.com/features/schema/integration-guidelines/).

### Breeze وObject Cache Pro

- Breeze هو Page Cache المعتمد، فلا يضاف WP Rocket.
- Object Cache Pro هو Persistent Object Cache المعتمد، فلا تضاف إضافة Redis cache أخرى.
- تخزن Quotes في Cache group خاص بالمنصة عبر WordPress APIs.
- لا تستخدم عملية مزامنة الأسعار Full Flush.
- `/go/*` يجب ألا يدخل Page Cache لأنه Redirect يسجل حدثاً.
- REST quote responses تختبر فعلياً مع Breeze/Varnish، ولا نفترض سلوكها قبل الاختبار.
- الإصدار المرصود من Object Cache Pro هو `1.25.5`؛ لا نعتمد على خصائص موثقة لإصدار 2.x.

مراجع التشغيل:

- [Breeze configuration and Never Cache URLs](https://support.cloudways.com/en/articles/5126470-how-to-install-and-configure-breeze-wordpress-cache-plugin)
- [Object Cache Pro flushing](https://objectcache.pro/docs/flushing)

### Antispam Bee

- مناسب كطبقة أولى لتعليقات WordPress القياسية.
- مراجعات الوسطاء ستستخدم WordPress Comments، لكن `comment_type` وطريقة الإرسال المخصصة يحتاجان اختبار توافق.
- إذا كان الإرسال عبر REST لا يمر بدورة التعليقات التي يفحصها Antispam Bee، تبقى الحماية مسؤولية الإضافة المخصصة أو Turnstile.
- Antispam Bee لا يعتبر حماية لتسجيل المستخدم أو جميع Form plugins.

مرجع القيود: [Antispam Bee on WordPress.org](https://wordpress.org/plugins/antispam-bee/).

### WP 2FA وWPS Hide Login

- WP 2FA مخصص لحسابات الإدارة والتحرير حالياً، وليس بديلاً عن صلاحيات `alborsa-platform`.
- نتحقق من سياسة الإلزام؛ حالة Active وحدها لا تكفي.
- WPS Hide Login يغير مسار الدخول، لذلك لا يجوز للإضافة المخصصة ربط أي زر بـ`/wp-login.php` يدوياً.
- يجب حفظ مسار الدخول وإجراءات الاستعادة في مدير أسرار/Runbook، وليس في مستودع عام.

مراجع التشغيل:

- [WP 2FA getting started](https://melapress.com/support/kb/wp-2fa-plugin-getting-started/)
- [WPS Hide Login](https://wordpress.org/plugins/wps-hide-login/)

## 3. اختبارات التوافق الإلزامية

| السيناريو | النجاح المطلوب |
|---|---|
| إنشاء وترجمة Asset | الحقول المحايدة منسوخة والوصف مستقل لكل لغة |
| حفظ Broker | Yoast يعرض Canonical وGraph واحداً فقط |
| تحديث Quote | Cache key المحدد يتغير بدون Full purge |
| زيارة `/go/{slug}` مرتين | تسجل الزيارتان وفق سياسة dedupe ولا تعاد استجابة مخزنة |
| إرسال Broker review | تمر بالمراجعة ومكافحة Spam بدون فقد Rating meta |
| تغيير Login slug | روابط الدخول التي تنشئها المنصة تظل صحيحة |
| تعطيل إضافة تكاملية في Staging | لا يحدث Fatal error وتظهر ملاحظة إدارية قابلة للتصرف |

## 4. سياسة التحديث المقترحة

- لا يتم تحديث إضافات الإنتاج لأول مرة مباشرة من لوحة التحكم.
- يؤخذ Backup قابل للاستعادة وتطبق التحديثات على Staging أولاً.
- يعاد تشغيل اختبارات التوافق أعلاه، خصوصاً Polylang وYoast وطبقتي الكاش.
- يسجل الإصدار السابق والجديد ونتيجة الاختبار وقرار النشر.
- التحديثات الأمنية الحرجة لها مسار عاجل، مع نفس شرط النسخة الاحتياطية واختبار Smoke مختصر.

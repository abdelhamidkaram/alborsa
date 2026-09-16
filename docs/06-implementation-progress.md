# سجل تقدم التنفيذ — alborsa-platform

> هذا الملف هو سجل تتبع للخطوات المنفذة وطابور المهام المفوضة القادمة. يوضح ما نُفذ فعلياً ويفصله بدقة عما هو مقترح أو قيد الانتظار، مع توضيح حدود التحقق التشغيلي.

## 1. ملخص الحالة العامة

- **التاريخ**: 2026-09-16
- **إصدار قاعدة البيانات المنفذ**: `2` (`alborsa_platform_db_version`)
- **الوحدات المسجلة**: `FoundationModule` و`EditorialModule`
- **حالة القرارات الحرجة**: القرار `D-002` معتمد؛ العربية على `/` من دون بادئة، والإنجليزية تحت `/en/`. سُجل نوعا التحليل والدليل، ولم تُسجل التصنيفات أو مسارات REST المخصصة بعد.

## 2. سجل الخطوات المنفذة

### خطوة 1: تأسيس طبقة Foundation (المرحلة 1)
- **الحالة**: مكتملة
- **المنفذ فعلياً**:
  - Plugin bootstrap مع Composer PSR-4 autoloading.
  - `ModuleRegistry` وواجهة `Module`.
  - وحدة `FoundationModule`.
  - `MigrationRunner` بإصدار Schema رقم `1` يهيئ صلاحية `manage_alborsa_platform` لدور `administrator`.
  - دورة تفعيل وتعطيل آمنة تدعم Multisite مع قفل ترحيل مؤقت (`alborsa_platform_migration_lock`).
  - فحوص Soft dependencies للإضافات السبع بدون Fatal errors عند غيابها.
  - فحصان داخل WordPress Site Health وأمر WP-CLI: `wp alborsa health`.
  - بوابات الجودة: PHPCS، PHPStan (Level 7)، واختبارات وحدة PHPUnit مع Brain Monkey.

### خطوة 2: ترحيل قاعدة بيانات جدول العلاقات (المرحلة 2 — الخطوة الأولى المحدودة)
- **الحالة**: مكتملة
- **المنفذ فعلياً**:
  - ترقية إصدار الـSchema من `1` إلى `2` في `MigrationRunner::LATEST_VERSION`.
  - إنشاء جدول العلاقات `{$wpdb->prefix}alb_relationships` بشكل idempotent باستخدام WordPress `dbDelta`.
  - الأعمدة المنفذة وفق وثيقة `docs/02-data-model.md`:
    - `id bigint(20) unsigned NOT NULL auto_increment`
    - `from_object_id bigint(20) unsigned NOT NULL`
    - `from_object_type varchar(32) NOT NULL`
    - `to_object_id bigint(20) unsigned NOT NULL`
    - `to_object_type varchar(32) NOT NULL`
    - `relation_type varchar(32) NOT NULL`
    - `sort_order int(11) NOT NULL default 0`
    - `created_at datetime NOT NULL` (حقل `datetime`؛ فرض توقيت UTC هو مسؤولية خدمة العلاقات عند الإدخال مستقبلاً)
  - المفاتيح والفهارس:
    - `PRIMARY KEY  (id)`
    - `UNIQUE KEY uq_relationship (from_object_id, from_object_type, to_object_id, to_object_type, relation_type)`
    - `KEY ix_target_lookup (to_object_id, to_object_type, relation_type)`
  - القيود المعمارية الملتزم بها:
    - عدم إضافة Foreign Keys نظراً لتعدد الكيانات المشار إليها عبر الأنواع المختلفة.
    - استخدام بادئة الجدول الحالية `{$wpdb->prefix}alb_relationships` (مثال لموقع مفرد: `wp_alb_relationships`) مع الاعتماد على دورة `Activation` الحالية لدعم Multisite.
    - حفظ الإصدار في قاعدة البيانات فقط بعد التحقق من وجود الجدول، مع إرجاع `WP_Error` وعدم رفع الإصدار عند الفشل.
    - الحفاظ التام على تكرارية تشغيل Migration الإصدار الأول والثاني بأمان (Idempotency).
  - الاختبارات:
    - 5 اختبارات PHPUnit معزولة وموجهة في `MigrationRunnerTest` تفحص صحة استعلام SQL، وترقية الإصدارين 1 و2 معاً، والترقية المباشرة من 1 إلى 2، وفشل إنشاء الجدول، وغياب اتصال قاعدة البيانات.
- **حدود التحقق التشغيلي**:
  - **بوابة الجودة الشاملة (`composer quality`)**: تم تشغيلها والتحقق منها بنجاح من قِبل الـOrchestrator المستقل بنتيجة نظيفة بالكامل:
    - **فحص بناء الجملة (PHP Lint)**: فحص 27 من 27 ملفاً بنجاح دون أي خطأ (27 PHP files linted).
    - **معايير ووردبريس القياسية (PHPCS)**: اجتياز كامل بدون أي أخطاء أو تحذيرات (PHPCS clean).
    - **التحليل الساكن المتقدم (PHPStan)**: المستوى السابع دون أخطاء (PHPStan Level 7, 0 errors).
    - **اختبارات الوحدة (PHPUnit)**: اجتياز كامل بنتيجة **17 tests, 56 assertions** دون أي إخفاق.
  - **حدود التحقق التكاملي والبيئة الحية**: التنفيذ الفعلي لـ`dbDelta` داخل قاعدة بيانات MySQL/MariaDB حية وتفعيل الشبكة عبر عدة مواقع (Multisite) يظلان حداً للتحقق التكاملي في بيئة ووردبريس حية.

### خطوة 3: أنواع المحتوى التحريرية الأساسية
- **الحالة**: مكتملة
- **المنفذ فعلياً**:
  - تسجيل `alb_analysis` و`alb_guide` كنوعي محتوى عامين يدعمان REST القياسي والمراجعات.
  - اعتماد rewrite bases: `/tahleel/` للتحليل و`/taleem/` للدليل، بدون الاعتماد على القالب.
  - إضافة خدمة Foundation ذات إصدار لمسح rewrite rules مرة واحدة بعد اكتمال تسجيل الوحدات، مع Soft Flush فقط.
  - تغطية التسجيل ودورة rewrite rules والتفعيل المفرد والشبكي باختبارات وحدة معزولة.

## 3. طابور خطوات التنفيذ القادمة

| الأولوية | المهمة | الحالة | الشروط المسبقة |
|---|---|---|---|
| 1 | حسم القرار D-002 بشأن بنية الروابط العربية | منفذة | العربية على `/` والإنجليزية تحت `/en/`؛ يبقى فحص إعداد Polylang على Staging. |
| 2 | تسجيل أنواع المحتوى الأساسية (CPTs): `alb_asset`، `alb_analysis`، `alb_guide` | قيد التنفيذ | `alb_analysis` و`alb_guide` منفذان؛ `alb_asset` هو التالي. |
| 3 | تسجيل التصنيفات (Taxonomies): `alb_topic`، `alb_asset_type`، إلخ | معلقة | تسجيل CPTs. |
| 4 | طبقة `RelationshipRepository` الداخلية لإدارة القراءة والكتابة والحذف الدقيق في `alb_relationships` | منفذة | لا تسجل Hooks أو REST أو Cache أو فحص وجود الكائنات؛ التكامل العام مع المحتوى ينتظر تسجيل الكيانات. |
| 5 | تسجيل الحقول الأساسية والتحقق منها عبر WordPress Meta APIs | معلقة | تسجيل CPTs |
| 6 | مسارات REST لقراءة الأصول والمحتوى | معلقة | اكتمال الخدمات والحقول |
| 7 | استيراد الأصول عبر CSV مع تقارير الأخطاء وDry-run | معلقة | استقرار CPT والحقول |
| 8 | إعداد Polylang و`wpml-config.xml` لمزامنة الحقول | معلقة | تسجيل CPTs والحقول |

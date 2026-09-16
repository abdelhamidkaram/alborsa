<?php
/**
 * Financial tools landing page.
 *
 * @package Alborsa
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tools = array(
	array(
		'name'   => __( 'حاسبة النقاط', 'alborsa' ),
		'topic'  => __( 'الفوركس', 'alborsa' ),
		'detail' => __( 'قيمة النقطة بحسب الزوج وحجم العقد وعملة الحساب.', 'alborsa' ),
		'state'  => __( 'ضمن MVP', 'alborsa' ),
	),
	array(
		'name'   => __( 'حاسبة حجم المركز', 'alborsa' ),
		'topic'  => __( 'إدارة المخاطر', 'alborsa' ),
		'detail' => __( 'تحويل نسبة المخاطرة ووقف الخسارة إلى حجم مركز مفهوم.', 'alborsa' ),
		'state'  => __( 'ضمن MVP', 'alborsa' ),
	),
	array(
		'name'   => __( 'حاسبة الربح والخسارة', 'alborsa' ),
		'topic'  => __( 'التداول', 'alborsa' ),
		'detail' => __( 'تقدير النتيجة من سعر الدخول والخروج والحجم والعملة.', 'alborsa' ),
		'state'  => __( 'ضمن MVP', 'alborsa' ),
	),
	array(
		'name'   => __( 'محوّل العملات', 'alborsa' ),
		'topic'  => __( 'أزواج الخليج', 'alborsa' ),
		'detail' => __( 'تحويل واضح يركز على SAR وAED وKWD والأزواج العالمية.', 'alborsa' ),
		'state'  => __( 'ينتظر مزود أسعار', 'alborsa' ),
	),
	array(
		'name'   => __( 'حاسبة نمو الاستثمار', 'alborsa' ),
		'topic'  => __( 'التخطيط', 'alborsa' ),
		'detail' => __( 'رأس المال والمساهمات والعائد والمدة مع افتراضات ظاهرة.', 'alborsa' ),
		'state'  => __( 'ضمن MVP', 'alborsa' ),
	),
	array(
		'name'   => __( 'حاسبة الزكاة', 'alborsa' ),
		'topic'  => __( 'الاستثمار والزكاة', 'alborsa' ),
		'detail' => __( 'سيناريوهات الاستثمار مع عرض المنهج والمدخلات والتنبيه الشرعي.', 'alborsa' ),
		'state'  => __( 'تنتظر اعتماد المنهج', 'alborsa' ),
	),
	array(
		'name'   => __( 'فلتر أسهم تداول', 'alborsa' ),
		'topic'  => __( 'الأسهم السعودية', 'alborsa' ),
		'detail' => __( 'بحث بحسب القطاع والقيمة السوقية والمضاعفات والعائد.', 'alborsa' ),
		'state'  => __( 'مرحلة لاحقة', 'alborsa' ),
	),
	array(
		'name'   => __( 'متتبع الاكتتابات', 'alborsa' ),
		'topic'  => __( 'تداول', 'alborsa' ),
		'detail' => __( 'مواعيد الاكتتاب والتخصيص والإدراج من مصادر رسمية.', 'alborsa' ),
		'state'  => __( 'ضمن P1', 'alborsa' ),
	),
	array(
		'name'   => __( 'تنبيهات الأسعار', 'alborsa' ),
		'topic'  => __( 'حسابات المستخدمين', 'alborsa' ),
		'detail' => __( 'قواعد سعرية وإشعارات للمستخدمين المسجلين.', 'alborsa' ),
		'state'  => __( 'ضمن P2', 'alborsa' ),
	),
);

get_header();
?>
<section class="section-hero section-hero--tools">
	<div class="site-container section-hero__inner section-hero__inner--split">
		<div>
			<p class="eyebrow"><?php esc_html_e( 'قرارات تبدأ من مدخلات واضحة', 'alborsa' ); ?></p>
			<h1><?php esc_html_e( 'الأدوات والحاسبات', 'alborsa' ); ?></h1>
			<p><?php esc_html_e( 'أدوات عربية تشرح المعادلة والافتراضات والنتيجة، من دون حفظ مدخلاتك افتراضياً.', 'alborsa' ); ?></p>
		</div>
		<div class="hero-state">
			<span class="status-dot status-dot--unavailable" aria-hidden="true"></span>
			<strong><?php esc_html_e( 'المعادلات قيد التنفيذ والاختبار', 'alborsa' ); ?></strong>
			<p><?php esc_html_e( 'لن تُعرض نماذج شكلية قبل وجود خدمة حساب مختبرة خلفها.', 'alborsa' ); ?></p>
		</div>
	</div>
</section>

<div class="site-container section-content section-stack">
	<section aria-labelledby="tools-directory-title">
		<header class="section-heading">
			<div>
				<p class="eyebrow"><?php esc_html_e( 'دليل الأدوات', 'alborsa' ); ?></p>
				<h2 id="tools-directory-title"><?php esc_html_e( 'كل أداة وحالتها الحالية', 'alborsa' ); ?></h2>
			</div>
		</header>
		<div class="tool-grid">
			<?php foreach ( $tools as $tool ) : ?>
				<article class="tool-card">
					<div class="tool-card__top">
						<span><?php echo esc_html( $tool['topic'] ); ?></span>
						<strong><?php echo esc_html( $tool['state'] ); ?></strong>
					</div>
					<h3><?php echo esc_html( $tool['name'] ); ?></h3>
					<p><?php echo esc_html( $tool['detail'] ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="principles-panel" aria-labelledby="tool-principles-title">
		<div><p class="eyebrow"><?php esc_html_e( 'معيار التنفيذ', 'alborsa' ); ?></p><h2 id="tool-principles-title"><?php esc_html_e( 'النتيجة وحدها لا تكفي', 'alborsa' ); ?></h2></div>
		<ol>
			<li><span>01</span><strong><?php esc_html_e( 'مدخلات محددة', 'alborsa' ); ?></strong><p><?php esc_html_e( 'وحدات العملة والنسب والفترات ظاهرة ولا تُستنتج بصمت.', 'alborsa' ); ?></p></li>
			<li><span>02</span><strong><?php esc_html_e( 'افتراضات مشروحة', 'alborsa' ); ?></strong><p><?php esc_html_e( 'التقريب والصيغة والمصدر جزء من نتيجة الأداة.', 'alborsa' ); ?></p></li>
			<li><span>03</span><strong><?php esc_html_e( 'خصوصية افتراضية', 'alborsa' ); ?></strong><p><?php esc_html_e( 'لا تُحفظ مدخلات الزائر ما لم توجد ميزة وحالة موافقة منفصلة.', 'alborsa' ); ?></p></li>
		</ol>
	</section>
</div>
<?php
get_footer();

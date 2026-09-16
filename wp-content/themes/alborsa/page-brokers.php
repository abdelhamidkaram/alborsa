<?php
/**
 * Broker reviews landing page before the broker module is implemented.
 *
 * @package Alborsa
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<section class="section-hero section-hero--brokers">
	<div class="site-container section-hero__inner section-hero__inner--split">
		<div>
			<p class="eyebrow"><?php esc_html_e( 'مراجعات مستقلة ومنظمة', 'alborsa' ); ?></p>
			<h1><?php esc_html_e( 'مراجعات الوسطاء', 'alborsa' ); ?></h1>
			<p><?php esc_html_e( 'ملفات عربية تراجع التنظيم والرسوم والمنصات والدعم العربي وطرق الإيداع الملائمة للسعودية والخليج.', 'alborsa' ); ?></p>
		</div>
		<div class="hero-state">
			<span class="status-dot status-dot--unavailable" aria-hidden="true"></span>
			<strong><?php esc_html_e( 'لا توجد مراجعة معتمدة بعد', 'alborsa' ); ?></strong>
			<p><?php esc_html_e( 'النشر ينتظر منهجية التقييم وسياسة التحقق وبيانات العروض الفعلية.', 'alborsa' ); ?></p>
		</div>
	</div>
</section>

<div class="site-container section-content section-stack">
	<section aria-labelledby="broker-pillars-title">
		<header class="section-heading">
			<div>
				<p class="eyebrow"><?php esc_html_e( 'ما الذي سنراجعه؟', 'alborsa' ); ?></p>
				<h2 id="broker-pillars-title"><?php esc_html_e( 'صورة كاملة قبل فتح الحساب', 'alborsa' ); ?></h2>
			</div>
		</header>
		<div class="feature-grid feature-grid--three">
			<article><span>01</span><h3><?php esc_html_e( 'التنظيم والترخيص', 'alborsa' ); ?></h3><p><?php esc_html_e( 'الكيان القانوني والجهة التنظيمية ورقم الترخيص ورابط المصدر وتاريخ آخر تحقق.', 'alborsa' ); ?></p></article>
			<article><span>02</span><h3><?php esc_html_e( 'التكلفة والمنصة', 'alborsa' ); ?></h3><p><?php esc_html_e( 'الرسوم والفروقات والمنصات والأدوات والحد الأدنى للإيداع.', 'alborsa' ); ?></p></article>
			<article><span>03</span><h3><?php esc_html_e( 'التجربة المحلية', 'alborsa' ); ?></h3><p><?php esc_html_e( 'الدعم العربي وطرق الإيداع والسحب مثل مدى والتحويل البنكي، بعد توثيقها.', 'alborsa' ); ?></p></article>
		</div>
	</section>

	<section class="comparison-preview" aria-labelledby="comparison-preview-title">
		<div>
			<p class="eyebrow"><?php esc_html_e( 'المقارنة', 'alborsa' ); ?></p>
			<h2 id="comparison-preview-title"><?php esc_html_e( 'قارن حتى ثلاثة وسطاء جنباً إلى جنب', 'alborsa' ); ?></h2>
			<p><?php esc_html_e( 'ستفصل المقارنة بين التقييم التحريري وتقييم المستخدمين، ولن تعرض شارة ترخيص أو دعوى توافق قبل وجود دليل موثق.', 'alborsa' ); ?></p>
		</div>
		<div class="comparison-slots" aria-label="<?php esc_attr_e( 'أماكن مقارنة الوسطاء', 'alborsa' ); ?>">
			<span><?php esc_html_e( 'وسيط 1', 'alborsa' ); ?></span>
			<span><?php esc_html_e( 'وسيط 2', 'alborsa' ); ?></span>
			<span><?php esc_html_e( 'وسيط 3', 'alborsa' ); ?></span>
		</div>
	</section>

	<section class="availability-panel" aria-labelledby="broker-status-title">
		<div><p class="eyebrow"><?php esc_html_e( 'حالة الإطلاق', 'alborsa' ); ?></p><h2 id="broker-status-title"><?php esc_html_e( 'ما الذي يمنع النشر الآن؟', 'alborsa' ); ?></h2></div>
		<ul class="availability-list">
			<li><strong><?php esc_html_e( 'منهجية التقييم', 'alborsa' ); ?></strong><span><?php esc_html_e( 'الأوزان ودليل الإثبات ودورية إعادة التحقق لم تُعتمد بعد.', 'alborsa' ); ?></span></li>
			<li><strong><?php esc_html_e( 'المراجعات الموثقة', 'alborsa' ); ?></strong><span><?php esc_html_e( 'لن تُستخدم شارة موثقة قبل وجود عملية إثبات مكتوبة.', 'alborsa' ); ?></span></li>
			<li><strong><?php esc_html_e( 'روابط فتح الحساب', 'alborsa' ); ?></strong><span><?php esc_html_e( 'لن تظهر أزرار عمولة قبل اعتماد الشركاء والإفصاح وسياسة القياس.', 'alborsa' ); ?></span></li>
		</ul>
	</section>
</div>
<?php
get_footer();

<?php
/**
 * Reusable published-broker directory and comparison interface.
 *
 * @package Alborsa
 */

declare(strict_types=1);

use function Alborsa\Theme\Content\term_names;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$brokers = isset( $args['brokers'] ) && is_array( $args['brokers'] ) ? $args['brokers'] : array();
?>
<section class="section-hero section-hero--brokers">
	<div class="site-container section-hero__inner section-hero__inner--split">
		<div>
			<p class="eyebrow"><?php esc_html_e( 'مراجعات مستقلة ومنظمة', 'alborsa' ); ?></p>
			<h1><?php esc_html_e( 'مراجعات الوسطاء', 'alborsa' ); ?></h1>
			<p><?php esc_html_e( 'ملفات عربية تفصل المعلومات المنشورة عن التقييم التحريري وتربط كل ادعاء تنظيمي بمصدر تحقق.', 'alborsa' ); ?></p>
		</div>
		<div class="hero-state">
			<span class="status-dot status-dot--unavailable" aria-hidden="true"></span>
			<strong><?php esc_html_e( 'منهجية التقييم قيد الاعتماد', 'alborsa' ); ?></strong>
			<p><?php esc_html_e( 'يمكن تصفح الملفات ومقارنة البيانات، لكن لا توجد توصية أو درجة تحريرية أو عرض عمولة حتى الآن.', 'alborsa' ); ?></p>
		</div>
	</div>
</section>

<div class="site-container section-content section-stack">
	<section aria-labelledby="broker-directory-title">
		<header class="section-heading">
			<div>
				<p class="eyebrow"><?php esc_html_e( 'الدليل المنشور', 'alborsa' ); ?></p>
				<h2 id="broker-directory-title"><?php esc_html_e( 'اختر وسيطين أو ثلاثة للمقارنة', 'alborsa' ); ?></h2>
				<p><?php esc_html_e( 'تعرض المقارنة الحقول الموثقة والمنشورة فقط، وتبقي التقييم التحريري والعروض التابعة معطلة.', 'alborsa' ); ?></p>
			</div>
		</header>

		<?php if ( array() !== $brokers ) : ?>
			<form class="broker-directory" data-alborsa-broker-comparison>
				<div class="broker-grid">
					<?php foreach ( $brokers as $broker ) : ?>
						<?php
						if ( ! $broker instanceof WP_Post ) {
							continue;
						}

						$broker_types  = term_names( $broker->ID, 'alb_broker_type' );
						$jurisdictions = term_names( $broker->ID, 'alb_jurisdiction' );
						$broker_url    = get_permalink( $broker );
						?>
						<article class="broker-card">
							<div class="broker-card__meta">
								<?php
								if ( '' !== $broker_types ) :
									?>
									<span><?php echo esc_html( $broker_types ); ?></span><?php endif; ?>
								<?php
								if ( '' !== $jurisdictions ) :
									?>
									<span><?php echo esc_html( $jurisdictions ); ?></span><?php endif; ?>
							</div>
							<h3><a href="<?php echo esc_url( is_string( $broker_url ) ? $broker_url : '' ); ?>"><?php echo esc_html( get_the_title( $broker ) ); ?></a></h3>
							<p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt( $broker ) ), 28 ) ); ?></p>
							<div class="broker-card__actions">
								<label>
									<input type="checkbox" name="broker_ids[]" value="<?php echo esc_attr( (string) $broker->ID ); ?>">
									<span><?php esc_html_e( 'أضف إلى المقارنة', 'alborsa' ); ?></span>
								</label>
								<a href="<?php echo esc_url( is_string( $broker_url ) ? $broker_url : '' ); ?>"><?php esc_html_e( 'عرض الملف', 'alborsa' ); ?></a>
							</div>
						</article>
					<?php endforeach; ?>
				</div>

				<div class="broker-comparison__controls">
					<button type="submit"><?php esc_html_e( 'قارن المختارين', 'alborsa' ); ?></button>
					<p data-broker-comparison-message aria-live="polite"></p>
				</div>
				<div class="broker-comparison__result" data-broker-comparison-result aria-live="polite"></div>
			</form>
		<?php else : ?>
			<div class="empty-state">
				<h3><?php esc_html_e( 'لم تُنشر ملفات وسطاء بعد', 'alborsa' ); ?></h3>
				<p><?php esc_html_e( 'يظهر الدليل بعد إدخال بيانات الوسيط ومراجعة المصادر ونشر الملف.', 'alborsa' ); ?></p>
			</div>
		<?php endif; ?>
	</section>

	<section class="availability-panel" aria-labelledby="broker-status-title">
		<div><p class="eyebrow"><?php esc_html_e( 'حدود واضحة', 'alborsa' ); ?></p><h2 id="broker-status-title"><?php esc_html_e( 'ما الذي لا ندّعيه بعد؟', 'alborsa' ); ?></h2></div>
		<ul class="availability-list">
			<li><strong><?php esc_html_e( 'التقييم التحريري', 'alborsa' ); ?></strong><span><?php esc_html_e( 'الأوزان ودليل الإثبات ودورية إعادة التحقق لم تُعتمد بعد.', 'alborsa' ); ?></span></li>
			<li><strong><?php esc_html_e( 'المراجعات الموثقة', 'alborsa' ); ?></strong><span><?php esc_html_e( 'تقييمات المستخدمين المنشورة منفصلة، ولا تحمل شارة موثقة قبل اعتماد عملية الإثبات.', 'alborsa' ); ?></span></li>
			<li><strong><?php esc_html_e( 'روابط فتح الحساب', 'alborsa' ); ?></strong><span><?php esc_html_e( 'لا تظهر أزرار عمولة قبل اعتماد الشركاء والإفصاح وسياسة القياس والاحتفاظ.', 'alborsa' ); ?></span></li>
		</ul>
	</section>
</div>

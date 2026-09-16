<?php
/**
 * Broker spotlight state before broker methodology and offers are approved.
 *
 * @package Alborsa
 */

declare(strict_types=1);

use function Alborsa\Theme\TemplateTags\section_url;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="broker-spotlight" aria-labelledby="broker-spotlight-title">
	<div class="site-container broker-spotlight__inner">
		<div>
			<p class="section-label"><?php esc_html_e( 'مراجعات الوسطاء', 'alborsa' ); ?></p>
			<h2 id="broker-spotlight-title"><?php esc_html_e( 'الوسيط المميز يبدأ من منهجية معلنة', 'alborsa' ); ?></h2>
			<p><?php esc_html_e( 'لن نعرض توصية أو رابط فتح حساب قبل توثيق التراخيص، أوزان التقييم، الإفصاح التجاري، وسياسة التحقق من المراجعات.', 'alborsa' ); ?></p>
		</div>
		<div class="broker-spotlight__state">
			<span class="status-dot status-dot--unavailable" aria-hidden="true"></span>
			<strong><?php esc_html_e( 'لا يوجد وسيط معتمد حالياً', 'alborsa' ); ?></strong>
			<a href="<?php echo esc_url( section_url( 'brokers' ) ); ?>"><?php esc_html_e( 'تعرف على قسم الوسطاء', 'alborsa' ); ?></a>
		</div>
	</div>
</section>

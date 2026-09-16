<?php
/**
 * Newsletter strip with an honest unavailable state.
 *
 * @package Alborsa
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="newsletter-strip" aria-labelledby="newsletter-strip-title">
	<div class="site-container newsletter-strip__inner">
		<div>
			<p class="section-label"><?php esc_html_e( 'النشرة البريدية', 'alborsa' ); ?></p>
			<h2 id="newsletter-strip-title"><?php esc_html_e( 'موجز الأسواق إلى بريدك', 'alborsa' ); ?></h2>
			<p><?php esc_html_e( 'قريباً: أهم الأخبار والتحليلات والمصطلحات المالية، في رسالة عربية واحدة.', 'alborsa' ); ?></p>
		</div>
		<p class="newsletter-strip__state">
			<span class="status-dot status-dot--unavailable" aria-hidden="true"></span>
			<strong><?php esc_html_e( 'الاشتراك غير متاح حتى اعتماد مزود البريد وسياسة الموافقة.', 'alborsa' ); ?></strong>
		</p>
	</div>
</section>

<?php
/**
 * Education archive pathway overview.
 *
 * @package Alborsa
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="education-overview" aria-labelledby="education-overview-title">
	<div>
		<p class="eyebrow"><?php esc_html_e( 'مسار متدرج', 'alborsa' ); ?></p>
		<h2 id="education-overview-title"><?php esc_html_e( 'ابدأ من مستواك وتقدم بثبات', 'alborsa' ); ?></h2>
	</div>
	<ol>
		<li><span>01</span><strong><?php esc_html_e( 'مبتدئ', 'alborsa' ); ?></strong><p><?php esc_html_e( 'أساسيات الأسواق والمخاطر والمصطلحات.', 'alborsa' ); ?></p></li>
		<li><span>02</span><strong><?php esc_html_e( 'متوسط', 'alborsa' ); ?></strong><p><?php esc_html_e( 'قراءة القوائم والتحليل وإدارة المحفظة.', 'alborsa' ); ?></p></li>
		<li><span>03</span><strong><?php esc_html_e( 'متقدم', 'alborsa' ); ?></strong><p><?php esc_html_e( 'بناء منهج قرار واختبار الفرضيات وإدارة المخاطر.', 'alborsa' ); ?></p></li>
	</ol>
</section>

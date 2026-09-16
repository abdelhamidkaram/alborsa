<?php
/**
 * Homepage editorial signal and lead briefing.
 *
 * @package Alborsa
 */

declare(strict_types=1);

use function Alborsa\Theme\HomeContent\post_summary;
use function Alborsa\Theme\TemplateTags\news_url;
use function Alborsa\Theme\TemplateTags\post_type_archive_url;
use function Alborsa\Theme\TemplateTags\section_url;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lead_story = isset( $args['lead_story'] ) && $args['lead_story'] instanceof WP_Post ? $args['lead_story'] : null;
?>
<section class="editorial-signal" aria-labelledby="editorial-signal-title">
	<div class="site-container editorial-signal__grid">
		<div class="editorial-signal__message">
			<p class="editorial-signal__promise"><?php esc_html_e( 'ثقة أعمق لقرار أوضح', 'alborsa' ); ?></p>
			<h1 id="editorial-signal-title"><?php esc_html_e( 'المعلومة الواضحة تغيّر القرار', 'alborsa' ); ?></h1>
			<p><?php esc_html_e( 'أخبار منتقاة، تحليل رصين، ومعرفة مالية محلية تشرح ما يحدث من دون مبالغة أو ضوضاء.', 'alborsa' ); ?></p>
			<a class="button button--gold" href="<?php echo esc_url( news_url() ); ?>"><?php esc_html_e( 'اقرأ موجز اليوم', 'alborsa' ); ?></a>
			<div class="signal-motif" aria-hidden="true">
				<span></span><span></span><span></span><span></span>
			</div>
		</div>

		<article class="editorial-signal__brief">
			<p class="section-label"><?php esc_html_e( 'موجز اليوم', 'alborsa' ); ?></p>
			<?php if ( $lead_story instanceof WP_Post ) : ?>
				<p class="content-meta"><?php echo esc_html( get_the_date( '', $lead_story ) ); ?></p>
				<h2><a href="<?php echo esc_url( get_permalink( $lead_story ) ); ?>"><?php echo esc_html( get_the_title( $lead_story ) ); ?></a></h2>
				<p><?php echo esc_html( post_summary( $lead_story ) ); ?></p>
				<a class="text-link" href="<?php echo esc_url( get_permalink( $lead_story ) ); ?>"><?php esc_html_e( 'اقرأ الخبر الكامل', 'alborsa' ); ?></a>
			<?php else : ?>
				<h2><?php esc_html_e( 'الموجز التحريري قيد الإعداد', 'alborsa' ); ?></h2>
				<p><?php esc_html_e( 'سيظهر هنا أول خبر منشور بعد مراجعته من فريق التحرير.', 'alborsa' ); ?></p>
			<?php endif; ?>
		</article>

		<nav class="editorial-signal__rail" aria-label="<?php esc_attr_e( 'استكشف أقسام البورصة', 'alborsa' ); ?>">
			<a href="<?php echo esc_url( section_url( 'aswaq' ) ); ?>">
				<span><?php esc_html_e( 'الأسواق', 'alborsa' ); ?></span>
				<strong><?php esc_html_e( 'سجل الأصول وحالة البيانات', 'alborsa' ); ?></strong>
			</a>
			<a href="<?php echo esc_url( post_type_archive_url( 'alb_analysis', 'tahleel' ) ); ?>">
				<span><?php esc_html_e( 'تحت المجهر', 'alborsa' ); ?></span>
				<strong><?php esc_html_e( 'قراءات تشرح سياق السوق', 'alborsa' ); ?></strong>
			</a>
			<a href="<?php echo esc_url( post_type_archive_url( 'alb_guide', 'taleem' ) ); ?>">
				<span><?php esc_html_e( 'التعليم', 'alborsa' ); ?></span>
				<strong><?php esc_html_e( 'مسارات مبسطة لبناء المعرفة', 'alborsa' ); ?></strong>
			</a>
		</nav>
	</div>
</section>

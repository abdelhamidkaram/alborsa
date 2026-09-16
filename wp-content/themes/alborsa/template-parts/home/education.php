<?php
/**
 * Homepage education pathway and newest guides.
 *
 * @package Alborsa
 */

declare(strict_types=1);

use function Alborsa\Theme\HomeContent\post_summary;
use function Alborsa\Theme\TemplateTags\post_type_archive_url;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$guides = isset( $args['guides'] ) && is_array( $args['guides'] ) ? $args['guides'] : array();
?>
<section class="education-path" aria-labelledby="education-path-title">
	<div class="site-container">
		<header class="module-heading">
			<div>
				<h2 id="education-path-title"><?php esc_html_e( 'رحلتك في التعليم', 'alborsa' ); ?></h2>
				<p><?php esc_html_e( 'دروس عربية تنتقل بك من المفاهيم الأساسية إلى قراءة السوق وإدارة المخاطر.', 'alborsa' ); ?></p>
			</div>
			<a href="<?php echo esc_url( post_type_archive_url( 'alb_guide', 'taleem' ) ); ?>"><?php esc_html_e( 'استكشف جميع الدروس', 'alborsa' ); ?></a>
		</header>

		<div class="education-path__levels" aria-label="<?php esc_attr_e( 'مستويات التعلم', 'alborsa' ); ?>">
			<span><?php esc_html_e( 'مبتدئ', 'alborsa' ); ?></span>
			<span><?php esc_html_e( 'متوسط', 'alborsa' ); ?></span>
			<span><?php esc_html_e( 'متقدم', 'alborsa' ); ?></span>
		</div>

		<?php if ( array() !== $guides ) : ?>
			<div class="education-path__list">
				<?php foreach ( $guides as $guide_post ) : ?>
					<?php if ( $guide_post instanceof WP_Post ) : ?>
						<article>
							<p class="content-meta"><?php echo esc_html( get_the_date( '', $guide_post ) ); ?></p>
							<h3><a href="<?php echo esc_url( get_permalink( $guide_post ) ); ?>"><?php echo esc_html( get_the_title( $guide_post ) ); ?></a></h3>
							<p><?php echo esc_html( post_summary( $guide_post ) ); ?></p>
						</article>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<p class="module-empty"><?php esc_html_e( 'لم تُنشر دروس بعد. سيظهر هنا أحدث محتوى تعليمي بعد مراجعته.', 'alborsa' ); ?></p>
		<?php endif; ?>
	</div>
</section>

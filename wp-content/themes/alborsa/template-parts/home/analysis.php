<?php
/**
 * Homepage analysis feature and list.
 *
 * @package Alborsa
 */

declare(strict_types=1);

use function Alborsa\Theme\HomeContent\post_summary;
use function Alborsa\Theme\TemplateTags\post_type_archive_url;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$analyses          = isset( $args['analyses'] ) && is_array( $args['analyses'] ) ? $args['analyses'] : array();
$featured_analysis = array_shift( $analyses );
?>
<section class="home-module home-analysis" aria-labelledby="home-analysis-title">
	<header class="module-heading">
		<div>
			<h2 id="home-analysis-title"><?php esc_html_e( 'تحت المجهر', 'alborsa' ); ?></h2>
			<p><?php esc_html_e( 'قراءات تربط الخبر بسياقه وتوضح ما نعرفه وما لم يتأكد بعد.', 'alborsa' ); ?></p>
		</div>
		<a href="<?php echo esc_url( post_type_archive_url( 'alb_analysis', 'tahleel' ) ); ?>"><?php esc_html_e( 'كل التحليلات', 'alborsa' ); ?></a>
	</header>

	<?php if ( $featured_analysis instanceof WP_Post ) : ?>
		<article class="analysis-feature">
			<?php if ( has_post_thumbnail( $featured_analysis ) ) : ?>
				<a class="analysis-feature__media" href="<?php echo esc_url( get_permalink( $featured_analysis ) ); ?>" tabindex="-1" aria-hidden="true">
					<?php echo wp_kses_post( get_the_post_thumbnail( $featured_analysis, 'alborsa-card', array( 'loading' => 'lazy' ) ) ); ?>
				</a>
			<?php endif; ?>
			<div>
				<p class="content-meta"><?php echo esc_html( get_the_date( '', $featured_analysis ) ); ?></p>
				<h3><a href="<?php echo esc_url( get_permalink( $featured_analysis ) ); ?>"><?php echo esc_html( get_the_title( $featured_analysis ) ); ?></a></h3>
				<p><?php echo esc_html( post_summary( $featured_analysis ) ); ?></p>
				<a class="text-link" href="<?php echo esc_url( get_permalink( $featured_analysis ) ); ?>"><?php esc_html_e( 'اقرأ التحليل', 'alborsa' ); ?></a>
			</div>
		</article>

		<?php if ( array() !== $analyses ) : ?>
			<div class="analysis-list">
				<?php foreach ( $analyses as $analysis_post ) : ?>
					<?php if ( $analysis_post instanceof WP_Post ) : ?>
						<article>
							<p class="content-meta"><?php echo esc_html( get_the_date( '', $analysis_post ) ); ?></p>
							<h3><a href="<?php echo esc_url( get_permalink( $analysis_post ) ); ?>"><?php echo esc_html( get_the_title( $analysis_post ) ); ?></a></h3>
						</article>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	<?php else : ?>
		<p class="module-empty"><?php esc_html_e( 'لا توجد تحليلات منشورة بعد. ستظهر القراءة الأحدث هنا بعد المراجعة.', 'alborsa' ); ?></p>
	<?php endif; ?>
</section>

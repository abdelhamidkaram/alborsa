<?php
/**
 * Singular editorial content template.
 *
 * @package Alborsa
 */

declare(strict_types=1);

use function Alborsa\Theme\Content\post_type_label;
use function Alborsa\Theme\Content\term_names;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( is_singular( 'alb_asset' ) ) {
	get_template_part( 'template-parts/content/asset-profile' );
	return;
}

get_header();

while ( have_posts() ) :
	the_post();
	$content_type = get_post_type();
	$topics       = term_names( get_the_ID(), 'alb_topic' );
	?>
	<article <?php post_class( 'singular-entry' ); ?>>
		<header class="singular-entry__header">
			<div class="site-container singular-entry__header-inner">
				<p class="eyebrow"><?php echo esc_html( post_type_label( is_string( $content_type ) ? $content_type : '' ) ); ?></p>
				<h1><?php the_title(); ?></h1>
				<div class="singular-entry__meta">
					<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
					<span><?php echo esc_html( get_the_author() ); ?></span>
					<?php if ( '' !== $topics ) : ?>
						<span><?php echo esc_html( $topics ); ?></span>
					<?php endif; ?>
				</div>
			</div>
		</header>

		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="site-container singular-entry__feature">
				<?php the_post_thumbnail( 'alborsa-feature', array( 'loading' => 'eager' ) ); ?>
				<?php if ( wp_get_attachment_caption( get_post_thumbnail_id() ) ) : ?>
					<figcaption><?php echo esc_html( wp_get_attachment_caption( get_post_thumbnail_id() ) ); ?></figcaption>
				<?php endif; ?>
			</figure>
		<?php endif; ?>

		<div class="site-container singular-entry__layout">
			<div class="entry-content">
				<?php the_content(); ?>
				<?php
				wp_link_pages(
					array(
						'before' => '<nav class="page-links" aria-label="' . esc_attr__( 'صفحات المقال', 'alborsa' ) . '">',
						'after'  => '</nav>',
					)
				);
				?>
			</div>

			<aside class="content-disclosure" aria-labelledby="content-disclosure-title">
				<h2 id="content-disclosure-title"><?php esc_html_e( 'قبل أن تقرأ', 'alborsa' ); ?></h2>
				<p><?php esc_html_e( 'هذا المحتوى إعلامي وتعليمي ولا يمثل نصيحة مالية شخصية. تحقق من المصدر والوقت وملاءمة القرار لوضعك.', 'alborsa' ); ?></p>
			</aside>
		</div>

		<nav class="site-container post-navigation" aria-label="<?php esc_attr_e( 'التنقل بين المحتوى', 'alborsa' ); ?>">
			<?php previous_post_link( '<div><span>' . esc_html__( 'السابق', 'alborsa' ) . '</span>%link</div>', '%title' ); ?>
			<?php next_post_link( '<div><span>' . esc_html__( 'التالي', 'alborsa' ) . '</span>%link</div>', '%title' ); ?>
		</nav>
	</article>
	<?php
endwhile;

get_footer();

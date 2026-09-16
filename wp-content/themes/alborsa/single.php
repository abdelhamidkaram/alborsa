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
	$content_id    = get_the_ID();
	$content_type  = get_post_type();
	$topics        = term_names( $content_id, 'alb_topic' );
	$source_name   = (string) get_post_meta( $content_id, 'alb_source_name', true );
	$source_url    = (string) get_post_meta( $content_id, 'alb_source_url', true );
	$reviewer_id   = absint( get_post_meta( $content_id, 'alb_reviewer_id', true ) );
	$last_reviewed = (string) get_post_meta( $content_id, 'alb_last_reviewed_at', true );
	$disclosure    = (string) get_post_meta( $content_id, 'alb_editorial_disclosure', true );
	$reviewer      = $reviewer_id > 0 ? get_userdata( $reviewer_id ) : false;
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
					<?php if ( '' !== $last_reviewed ) : ?>
						<?php /* translators: %s: date of the latest editorial review. */ ?>
						<span><?php echo esc_html( sprintf( __( 'آخر مراجعة: %s', 'alborsa' ), $last_reviewed ) ); ?></span>
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
				<h2 id="content-disclosure-title"><?php esc_html_e( 'المصدر والمراجعة', 'alborsa' ); ?></h2>
				<?php if ( '' !== $source_name ) : ?>
					<p><strong><?php esc_html_e( 'المصدر:', 'alborsa' ); ?></strong>
					<?php if ( '' !== $source_url ) : ?>
						<a href="<?php echo esc_url( $source_url ); ?>" rel="noopener noreferrer"><?php echo esc_html( $source_name ); ?></a>
					<?php else : ?>
						<?php echo esc_html( $source_name ); ?>
					<?php endif; ?>
					</p>
				<?php endif; ?>
				<?php if ( $reviewer instanceof WP_User ) : ?>
					<p><strong><?php esc_html_e( 'راجع المحتوى:', 'alborsa' ); ?></strong> <?php echo esc_html( $reviewer->display_name ); ?></p>
				<?php endif; ?>
				<?php if ( '' !== $last_reviewed ) : ?>
					<p><strong><?php esc_html_e( 'تاريخ المراجعة:', 'alborsa' ); ?></strong> <time datetime="<?php echo esc_attr( $last_reviewed ); ?>"><?php echo esc_html( $last_reviewed ); ?></time></p>
				<?php endif; ?>
				<?php if ( '' !== $disclosure ) : ?>
					<p><?php echo esc_html( $disclosure ); ?></p>
				<?php endif; ?>
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

<?php
/**
 * Default page template.
 *
 * @package Alborsa
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	if ( Alborsa\Theme\StaticPages\is_static_page( get_the_ID() ) ) {
		get_template_part( 'template-parts/content/static-page' );
		continue;
	}
	?>
	<article <?php post_class( 'standard-page' ); ?>>
		<header class="section-hero">
			<div class="site-container section-hero__inner">
				<p class="eyebrow"><?php esc_html_e( 'البورصة', 'alborsa' ); ?></p>
				<h1><?php the_title(); ?></h1>
			</div>
		</header>
		<div class="site-container standard-page__content entry-content">
			<?php the_content(); ?>
		</div>
	</article>
	<?php
endwhile;

get_footer();

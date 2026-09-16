<?php
/**
 * Editorial content card.
 *
 * @package Alborsa
 */

declare(strict_types=1);

use function Alborsa\Theme\Content\post_type_label;
use function Alborsa\Theme\HomeContent\post_summary;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$card_post = isset( $args['post'] ) && $args['post'] instanceof WP_Post ? $args['post'] : null;

if ( ! $card_post instanceof WP_Post ) {
	return;
}
?>
<article <?php post_class( 'editorial-card', $card_post->ID ); ?>>
	<?php if ( has_post_thumbnail( $card_post ) ) : ?>
		<a class="editorial-card__media" href="<?php echo esc_url( get_permalink( $card_post ) ); ?>" tabindex="-1" aria-hidden="true">
			<?php echo wp_kses_post( get_the_post_thumbnail( $card_post, 'alborsa-card', array( 'loading' => 'lazy' ) ) ); ?>
		</a>
	<?php endif; ?>
	<div class="editorial-card__body">
		<p class="editorial-card__meta">
			<span><?php echo esc_html( post_type_label( $card_post->post_type ) ); ?></span>
			<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $card_post ) ); ?>"><?php echo esc_html( get_the_date( '', $card_post ) ); ?></time>
		</p>
		<h2><a href="<?php echo esc_url( get_permalink( $card_post ) ); ?>"><?php echo esc_html( get_the_title( $card_post ) ); ?></a></h2>
		<p><?php echo esc_html( post_summary( $card_post ) ); ?></p>
	</div>
</article>

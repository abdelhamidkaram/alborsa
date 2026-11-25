<?php
/**
 * One news archive row.
 *
 * @package Alborsa
 */

declare(strict_types=1);

use function Alborsa\Theme\Content\post_type_label;
use function Alborsa\Theme\Content\term_names;
use function Alborsa\Theme\HomeContent\post_summary;
use function Alborsa\Theme\NewsContent\reading_minutes;
use function Alborsa\Theme\NewsContent\reading_label;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$news_post = isset( $args['post'] ) && $args['post'] instanceof WP_Post ? $args['post'] : null;

if ( ! $news_post instanceof WP_Post ) {
	return;
}

$is_analysis = 'alb_analysis' === $news_post->post_type;
$topics      = term_names( $news_post->ID, 'alb_topic' );
?>
<article class="news-feed-item<?php echo $is_analysis ? ' news-feed-item--analysis' : ''; ?>">
	<time class="news-feed-item__time" datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $news_post ) ); ?>"><?php echo esc_html( get_the_time( 'H:i', $news_post ) ); ?></time>
	<div class="news-feed-item__body">
		<?php if ( $is_analysis && has_post_thumbnail( $news_post ) ) : ?>
			<a class="news-feed-item__media" href="<?php echo esc_url( get_permalink( $news_post ) ); ?>" tabindex="-1" aria-hidden="true">
				<?php echo wp_kses_post( get_the_post_thumbnail( $news_post, 'alborsa-card', array( 'loading' => 'lazy' ) ) ); ?>
			</a>
		<?php endif; ?>
		<div class="news-feed-item__content">
			<div class="news-feed-item__meta">
				<span class="news-feed-item__type"><?php echo esc_html( post_type_label( $news_post->post_type ) ); ?></span>
				<?php
				if ( '' !== $topics ) :
					?>
					<span><?php echo esc_html( $topics ); ?></span><?php endif; ?>
			</div>
			<h3><a href="<?php echo esc_url( get_permalink( $news_post ) ); ?>"><?php echo esc_html( get_the_title( $news_post ) ); ?></a></h3>
			<?php if ( $is_analysis ) : ?>
				<p class="news-feed-item__summary"><?php echo esc_html( post_summary( $news_post ) ); ?></p>
				<p class="news-feed-item__byline">
					<strong><?php echo esc_html( get_the_author_meta( 'display_name', (int) $news_post->post_author ) ); ?></strong>
					<span><?php echo esc_html( reading_label( reading_minutes( $news_post ) ) ); ?></span>
				</p>
			<?php endif; ?>
		</div>
	</div>
</article>

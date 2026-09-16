<?php
/**
 * Scrollable latest-news strip.
 *
 * @package Alborsa
 */

declare(strict_types=1);

use function Alborsa\Theme\TemplateTags\news_url;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$latest_news = isset( $args['news'] ) && is_array( $args['news'] ) ? $args['news'] : array();
?>
<section class="latest-news-strip" aria-labelledby="latest-news-title">
	<div class="site-container latest-news-strip__inner">
		<header>
			<h2 id="latest-news-title"><?php esc_html_e( 'آخر الأخبار', 'alborsa' ); ?></h2>
			<a href="<?php echo esc_url( news_url() ); ?>"><?php esc_html_e( 'كل الأخبار', 'alborsa' ); ?></a>
		</header>
		<?php if ( array() !== $latest_news ) : ?>
			<ol aria-label="<?php esc_attr_e( 'أحدث خمسة أخبار', 'alborsa' ); ?>">
				<?php foreach ( $latest_news as $news_post ) : ?>
					<?php if ( $news_post instanceof WP_Post ) : ?>
						<li>
							<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $news_post ) ); ?>"><?php echo esc_html( get_the_time( 'H:i', $news_post ) ); ?></time>
							<a href="<?php echo esc_url( get_permalink( $news_post ) ); ?>"><?php echo esc_html( get_the_title( $news_post ) ); ?></a>
						</li>
					<?php endif; ?>
				<?php endforeach; ?>
			</ol>
		<?php else : ?>
			<p class="module-empty"><?php esc_html_e( 'لا توجد أخبار منشورة بعد. ستظهر هنا أحدث التغطيات بعد مراجعتها.', 'alborsa' ); ?></p>
		<?php endif; ?>
	</div>
</section>

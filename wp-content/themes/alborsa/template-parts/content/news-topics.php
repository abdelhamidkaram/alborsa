<?php
/**
 * Existing editorial categories for the news index.
 *
 * @package Alborsa
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$news_categories = get_categories(
	array(
		'hide_empty' => true,
		'number'     => 8,
		'orderby'    => 'count',
		'order'      => 'DESC',
	)
);

if ( is_wp_error( $news_categories ) || array() === $news_categories ) {
	return;
}
?>
<nav class="topic-navigation" aria-label="<?php esc_attr_e( 'تصنيفات الأخبار', 'alborsa' ); ?>">
	<span><?php esc_html_e( 'التصنيفات', 'alborsa' ); ?></span>
	<ul>
		<?php foreach ( $news_categories as $news_category ) : ?>
			<?php $category_url = get_category_link( $news_category ); ?>
			<?php if ( ! is_wp_error( $category_url ) ) : ?>
				<li><a href="<?php echo esc_url( $category_url ); ?>"><?php echo esc_html( $news_category->name ); ?></a></li>
			<?php endif; ?>
		<?php endforeach; ?>
	</ul>
</nav>

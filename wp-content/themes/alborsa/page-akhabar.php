<?php
/**
 * News page fallback when /akhabar/ is configured as a regular page.
 *
 * @package Alborsa
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_page_number = (int) max( 1, get_query_var( 'paged' ), get_query_var( 'page' ) );
$posts_per_page      = min( 24, max( 1, (int) get_option( 'posts_per_page', 10 ) ) );
$news_query          = new WP_Query(
	array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => $posts_per_page,
		'paged'               => $current_page_number,
		'ignore_sticky_posts' => false,
		'suppress_filters'    => false,
	)
);

get_header();
?>
<section class="section-hero section-hero--news">
	<div class="site-container section-hero__inner">
		<p class="eyebrow"><?php esc_html_e( 'غرفة الأخبار', 'alborsa' ); ?></p>
		<h1><?php esc_html_e( 'الأخبار', 'alborsa' ); ?></h1>
		<p><?php esc_html_e( 'تغطية مالية عربية منتقاة لأسواق السعودية والخليج والفوركس والسلع والعملات الرقمية.', 'alborsa' ); ?></p>
	</div>
</section>
<div class="site-container section-content">
	<?php get_template_part( 'template-parts/content/news-topics' ); ?>
	<?php if ( $news_query->have_posts() ) : ?>
		<div class="editorial-grid">
			<?php
			while ( $news_query->have_posts() ) :
				$news_query->the_post();
				get_template_part( 'template-parts/content/card', null, array( 'post' => get_post() ) );
			endwhile;
			?>
		</div>
		<?php
		the_posts_pagination(
			array(
				'total'     => $news_query->max_num_pages,
				'current'   => $current_page_number,
				'mid_size'  => 1,
				'prev_text' => __( 'السابق', 'alborsa' ),
				'next_text' => __( 'التالي', 'alborsa' ),
			)
		);
		?>
	<?php else : ?>
		<section class="empty-state" aria-labelledby="news-empty-title"><h2 id="news-empty-title"><?php esc_html_e( 'لا توجد أخبار منشورة بعد', 'alborsa' ); ?></h2><p><?php esc_html_e( 'ستظهر هنا التغطيات بعد مراجعتها من فريق التحرير.', 'alborsa' ); ?></p></section>
	<?php endif; ?>
</div>
<?php
wp_reset_postdata();
get_footer();

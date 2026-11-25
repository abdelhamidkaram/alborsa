<?php
/**
 * Shared Figma-matched news and analysis archive.
 *
 * @package Alborsa
 */

declare(strict_types=1);

use function Alborsa\Theme\NewsContent\archive_page_number;
use function Alborsa\Theme\NewsContent\archive_query;
use function Alborsa\Theme\NewsContent\archive_topics;
use function Alborsa\Theme\NewsContent\asset_tags;
use function Alborsa\Theme\NewsContent\date_group_key;
use function Alborsa\Theme\NewsContent\date_group_label;
use function Alborsa\Theme\NewsContent\filter_url;
use function Alborsa\Theme\NewsContent\published_counts;
use function Alborsa\Theme\NewsContent\selected_topic;
use function Alborsa\Theme\NewsContent\selected_type;
use function Alborsa\Theme\NewsContent\urgent_posts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_page   = archive_page_number();
$selected_type  = selected_type();
$selected_topic = selected_topic();
$news_query     = archive_query( $current_page, $selected_type, $selected_topic );
$counts         = published_counts();
$topics         = archive_topics();
$urgent_posts   = urgent_posts();
$asset_tags     = asset_tags();
$type_tabs      = array(
	'all'      => __( 'الكل', 'alborsa' ),
	'news'     => __( 'الأخبار', 'alborsa' ),
	'analysis' => __( 'التحليلات', 'alborsa' ),
);
?>
<section class="news-index" aria-labelledby="news-index-title">
	<div class="site-container news-index__container">
		<nav class="news-breadcrumbs" aria-label="<?php esc_attr_e( 'مسار التنقل', 'alborsa' ); ?>">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'الرئيسية', 'alborsa' ); ?></a>
			<span aria-hidden="true">‹</span>
			<span aria-current="page"><?php esc_html_e( 'الأخبار والتحليلات', 'alborsa' ); ?></span>
		</nav>

		<header class="news-index__header">
			<h1 id="news-index-title"><?php esc_html_e( 'الأخبار والتحليلات', 'alborsa' ); ?></h1>
			<p><?php esc_html_e( 'ما يحدث في أسواق الخليج، وقراءة فريقنا لما وراءه.', 'alborsa' ); ?></p>
		</header>

		<nav class="news-type-tabs" aria-label="<?php esc_attr_e( 'أنواع المحتوى', 'alborsa' ); ?>">
			<ul>
				<?php foreach ( $type_tabs as $tab_key => $tab_label ) : ?>
					<li>
						<a href="<?php echo esc_url( filter_url( $tab_key, $selected_topic ) ); ?>" <?php echo $selected_type === $tab_key ? 'aria-current="page"' : ''; ?>>
							<span><?php echo esc_html( $tab_label ); ?></span>
							<strong class="numeric"><?php echo esc_html( (string) $counts[ $tab_key ] ); ?></strong>
						</a>
					</li>
				<?php endforeach; ?>
				<li><span class="news-type-tabs__disabled" aria-disabled="true"><span><?php esc_html_e( 'التوقعات', 'alborsa' ); ?></span><small><?php esc_html_e( 'قريباً', 'alborsa' ); ?></small></span></li>
			</ul>
		</nav>

		<nav class="news-topic-filters" aria-label="<?php esc_attr_e( 'موضوعات الأخبار', 'alborsa' ); ?>">
			<a class="news-topic-filter<?php echo '' === $selected_topic ? ' is-active' : ''; ?>" href="<?php echo esc_url( filter_url( $selected_type, '' ) ); ?>" <?php echo '' === $selected_topic ? 'aria-current="page"' : ''; ?>><?php esc_html_e( 'الكل', 'alborsa' ); ?></a>
			<?php foreach ( $topics as $topic ) : ?>
				<a class="news-topic-filter<?php echo $selected_topic === $topic->slug ? ' is-active' : ''; ?>" href="<?php echo esc_url( filter_url( $selected_type, $topic->slug ) ); ?>" <?php echo $selected_topic === $topic->slug ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $topic->name ); ?></a>
			<?php endforeach; ?>
		</nav>

		<div class="news-index__layout">
			<div class="news-feed">
				<?php if ( $news_query->have_posts() ) : ?>
					<?php $current_group = ''; ?>
					<?php foreach ( $news_query->posts as $news_post ) : ?>
						<?php $next_group = date_group_key( $news_post ); ?>
						<?php if ( $next_group !== $current_group ) : ?>
							<header class="news-feed__day">
								<h2><?php echo esc_html( date_group_label( $news_post ) ); ?></h2>
								<time datetime="<?php echo esc_attr( get_the_date( 'Y-m-d', $news_post ) ); ?>"><?php echo esc_html( get_the_date( 'j F', $news_post ) ); ?></time>
							</header>
							<?php $current_group = $next_group; ?>
						<?php endif; ?>
						<?php get_template_part( 'template-parts/content/news-row', null, array( 'post' => $news_post ) ); ?>
					<?php endforeach; ?>

					<?php
					$pagination_url = filter_url( $selected_type, $selected_topic );
					$pagination     = paginate_links(
						array(
							'base'      => $pagination_url . '%_%',
							'format'    => ( str_contains( $pagination_url, '?' ) ? '&' : '?' ) . 'news_page=%#%',
							'current'   => $current_page,
							'total'     => max( 1, (int) $news_query->max_num_pages ),
							'mid_size'  => 1,
							'prev_text' => __( 'السابق', 'alborsa' ),
							'next_text' => __( 'التالي', 'alborsa' ),
							'type'      => 'list',
						)
					);
					?>
					<?php if ( is_string( $pagination ) ) : ?>
						<nav class="news-pagination" aria-label="<?php esc_attr_e( 'صفحات الأخبار', 'alborsa' ); ?>"><?php echo wp_kses_post( $pagination ); ?></nav>
					<?php endif; ?>
				<?php else : ?>
					<section class="empty-state news-feed__empty" aria-labelledby="news-empty-title">
						<h2 id="news-empty-title"><?php esc_html_e( 'لا توجد نتائج مطابقة', 'alborsa' ); ?></h2>
						<p><?php esc_html_e( 'غيّر نوع المحتوى أو الموضوع لعرض مواد أخرى منشورة.', 'alborsa' ); ?></p>
						<a class="button button--secondary" href="<?php echo esc_url( filter_url( 'all', '' ) ); ?>"><?php esc_html_e( 'عرض كل الأخبار', 'alborsa' ); ?></a>
					</section>
				<?php endif; ?>
			</div>

			<aside class="news-sidebar" aria-label="<?php esc_attr_e( 'ملخص غرفة الأخبار', 'alborsa' ); ?>">
				<section class="news-urgent" aria-labelledby="news-urgent-title">
					<h2 id="news-urgent-title"><?php esc_html_e( 'أحدث الأخبار', 'alborsa' ); ?></h2>
					<?php if ( array() !== $urgent_posts ) : ?>
						<ol>
							<?php foreach ( $urgent_posts as $urgent_post ) : ?>
								<li>
									<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $urgent_post ) ); ?>"><?php echo esc_html( get_the_time( 'H:i', $urgent_post ) ); ?></time>
									<a href="<?php echo esc_url( get_permalink( $urgent_post ) ); ?>"><?php echo esc_html( get_the_title( $urgent_post ) ); ?></a>
								</li>
							<?php endforeach; ?>
						</ol>
					<?php else : ?>
						<p class="news-urgent__empty"><?php esc_html_e( 'لا توجد أخبار منشورة حالياً.', 'alborsa' ); ?></p>
					<?php endif; ?>
					<button class="news-urgent__button" type="button" disabled><?php esc_html_e( 'تنبيهات الأخبار غير متاحة حالياً', 'alborsa' ); ?></button>
					<p class="news-urgent__note"><?php esc_html_e( 'ستُفعّل التنبيهات بعد إطلاق حسابات المستخدمين.', 'alborsa' ); ?></p>
				</section>

				<section class="news-assets" aria-labelledby="news-assets-title">
					<h2 id="news-assets-title"><?php esc_html_e( 'وسوم الأصول', 'alborsa' ); ?></h2>
					<?php if ( array() !== $asset_tags ) : ?>
						<ul>
							<?php foreach ( $asset_tags as $asset ) : ?>
								<li><a href="<?php echo esc_url( $asset['url'] ); ?>" dir="auto"><?php echo esc_html( '' !== $asset['symbol'] ? $asset['symbol'] : $asset['title'] ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					<?php else : ?>
						<p><?php esc_html_e( 'ستظهر الأصول هنا بعد نشر سجل الأصول.', 'alborsa' ); ?></p>
					<?php endif; ?>
				</section>
			</aside>
		</div>
	</div>
</section>
<?php wp_reset_postdata(); ?>

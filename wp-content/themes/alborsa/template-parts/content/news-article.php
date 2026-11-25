<?php
/**
 * Figma-matched single news article.
 *
 * @package Alborsa
 */

declare(strict_types=1);

use function Alborsa\Theme\Content\post_type_label;
use function Alborsa\Theme\Content\term_names;
use function Alborsa\Theme\NewsContent\mark_investor_heading;
use function Alborsa\Theme\NewsContent\provenance;
use function Alborsa\Theme\NewsContent\reading_minutes;
use function Alborsa\Theme\NewsContent\reading_label;
use function Alborsa\Theme\NewsContent\related_posts;
use function Alborsa\Theme\TemplateTags\news_url;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

while ( have_posts() ) :
	the_post();
	$article       = get_post();
	$topics        = term_names( $article->ID, 'alb_topic' );
	$reading_time  = reading_minutes( $article );
	$provenance    = provenance( $article );
	$related       = related_posts( $article );
	$excerpt       = trim( (string) get_the_excerpt( $article ) );
	$investor_note = post_password_required( $article ) ? '' : (string) get_post_meta( $article->ID, 'alb_investor_note', true );
	?>
	<article <?php post_class( 'news-article' ); ?>>
		<div class="site-container news-article__shell">
			<nav class="news-breadcrumbs" aria-label="<?php esc_attr_e( 'مسار التنقل', 'alborsa' ); ?>">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'الرئيسية', 'alborsa' ); ?></a>
				<span aria-hidden="true">‹</span>
				<a href="<?php echo esc_url( news_url() ); ?>"><?php esc_html_e( 'الأخبار', 'alborsa' ); ?></a>
				<span aria-hidden="true">‹</span>
				<span aria-current="page"><?php the_title(); ?></span>
			</nav>

			<header class="news-article__header">
				<h1><?php the_title(); ?></h1>
				<?php
				if ( '' !== $excerpt ) :
					?>
					<p class="news-article__dek"><?php echo esc_html( $excerpt ); ?></p><?php endif; ?>
				<div class="news-article__meta">
					<strong><?php echo esc_html( get_the_author() ); ?></strong>
					<?php /* translators: 1: publication date; 2: publication time. */ ?>
					<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( sprintf( __( '%1$s، %2$s', 'alborsa' ), get_the_date(), get_the_time( 'H:i' ) ) ); ?></time>
					<span><?php echo esc_html( reading_label( $reading_time ) ); ?></span>
				</div>
				<div class="news-article__actions">
					<button class="news-article__action" type="button" disabled title="<?php esc_attr_e( 'يتاح الحفظ بعد إطلاق حسابات المستخدمين', 'alborsa' ); ?>">
						<svg aria-hidden="true" width="19" height="19" viewBox="0 0 24 24" fill="none"><path d="M6.75 4.75h10.5v14.5L12 16l-5.25 3.25V4.75Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" /></svg>
						<span><?php esc_html_e( 'حفظ المقال', 'alborsa' ); ?></span>
					</button>
					<button class="news-article__action" type="button" data-news-share data-share-url="<?php echo esc_url( get_permalink() ); ?>" data-share-title="<?php echo esc_attr( get_the_title() ); ?>">
						<svg aria-hidden="true" width="19" height="19" viewBox="0 0 24 24" fill="none"><circle cx="18" cy="5" r="2.25" stroke="currentColor" stroke-width="1.7" /><circle cx="6" cy="12" r="2.25" stroke="currentColor" stroke-width="1.7" /><circle cx="18" cy="19" r="2.25" stroke="currentColor" stroke-width="1.7" /><path d="m8 11 7.8-4.6M8 13l7.8 4.6" stroke="currentColor" stroke-width="1.7" /></svg>
						<span><?php esc_html_e( 'شارك', 'alborsa' ); ?></span>
					</button>
					<p class="news-article__share-status" aria-live="polite" data-share-status></p>
				</div>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="news-article__feature">
					<?php the_post_thumbnail( 'alborsa-feature', array( 'loading' => 'eager' ) ); ?>
					<?php
					if ( wp_get_attachment_caption( get_post_thumbnail_id() ) ) :
						?>
						<figcaption><?php echo esc_html( wp_get_attachment_caption( get_post_thumbnail_id() ) ); ?></figcaption><?php endif; ?>
				</figure>
			<?php endif; ?>

			<div class="entry-content news-article__body">
				<?php
				$investor_heading_filter = mark_investor_heading( ... );
				if ( '' === $investor_note ) {
					add_filter( 'the_content', $investor_heading_filter, 20 );
				}
				the_content();
				remove_filter( 'the_content', $investor_heading_filter, 20 );
				?>

				<?php if ( '' !== $investor_note ) : ?>
					<section class="news-investor-note" aria-labelledby="news-investor-title">
						<h2 class="news-investor-heading" id="news-investor-title"><?php esc_html_e( 'ماذا يعني هذا للمستثمر؟', 'alborsa' ); ?></h2>
						<?php echo wp_kses_post( wpautop( esc_html( $investor_note ) ) ); ?>
					</section>
				<?php endif; ?>

				<?php
				wp_link_pages(
					array(
						'before' => '<nav class="page-links" aria-label="' . esc_attr__( 'صفحات المقال', 'alborsa' ) . '">',
						'after'  => '</nav>',
					)
				);
				?>
			</div>

			<?php if ( '' !== $provenance['source_name'] || $provenance['reviewer'] instanceof WP_User || '' !== $provenance['last_reviewed'] || '' !== $provenance['disclosure'] ) : ?>
				<aside class="news-provenance" aria-labelledby="news-provenance-title">
					<h2 id="news-provenance-title"><?php esc_html_e( 'المصدر والمراجعة', 'alborsa' ); ?></h2>
					<?php if ( '' !== $provenance['source_name'] ) : ?>
						<p><strong><?php esc_html_e( 'المصدر:', 'alborsa' ); ?></strong>
						<?php
						if ( '' !== $provenance['source_url'] ) :
							?>
							<a href="<?php echo esc_url( $provenance['source_url'] ); ?>" rel="noopener noreferrer"><?php echo esc_html( $provenance['source_name'] ); ?></a>
							<?php
else :
	?>
							<?php echo esc_html( $provenance['source_name'] ); ?><?php endif; ?>
						</p>
					<?php endif; ?>
					<?php
					if ( $provenance['reviewer'] instanceof WP_User ) :
						?>
						<p><strong><?php esc_html_e( 'راجع المحتوى:', 'alborsa' ); ?></strong> <?php echo esc_html( $provenance['reviewer']->display_name ); ?></p><?php endif; ?>
					<?php
					if ( '' !== $provenance['last_reviewed'] ) :
						?>
						<p><strong><?php esc_html_e( 'آخر مراجعة:', 'alborsa' ); ?></strong> <time datetime="<?php echo esc_attr( $provenance['last_reviewed'] ); ?>"><?php echo esc_html( $provenance['last_reviewed'] ); ?></time></p><?php endif; ?>
					<?php
					if ( '' !== $provenance['disclosure'] ) :
						?>
						<p><?php echo esc_html( $provenance['disclosure'] ); ?></p><?php endif; ?>
				</aside>
			<?php endif; ?>

			<?php if ( array() !== $related ) : ?>
				<section class="news-related" aria-labelledby="news-related-title">
					<h2 id="news-related-title"><?php esc_html_e( 'أخبار ذات صلة', 'alborsa' ); ?></h2>
					<ul>
						<?php foreach ( $related as $related_post ) : ?>
							<li>
								<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $related_post ) ); ?>"><?php echo esc_html( get_the_time( 'H:i', $related_post ) ); ?></time>
								<div>
									<a href="<?php echo esc_url( get_permalink( $related_post ) ); ?>"><?php echo esc_html( get_the_title( $related_post ) ); ?></a>
									<p><span><?php echo esc_html( post_type_label( $related_post->post_type ) ); ?></span>
									<?php
									if ( '' !== term_names( $related_post->ID, 'alb_topic' ) ) :
										?>
										<span><?php echo esc_html( term_names( $related_post->ID, 'alb_topic' ) ); ?></span><?php endif; ?></p>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>

			<aside class="news-article__disclaimer">
				<svg aria-hidden="true" width="18" height="18" viewBox="0 0 18 18" fill="none"><circle cx="9" cy="9" r="6.75" stroke="currentColor" stroke-width="1.5" /><path d="M9 8.25v4M9 5.75h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" /></svg>
				<p><?php esc_html_e( 'هذا المحتوى للمعلومات والتعليم وليس نصيحة مالية. الأداء السابق لا يضمن النتائج المستقبلية، وقرار الاستثمار ومسؤوليته يعودان إليك.', 'alborsa' ); ?></p>
			</aside>
		</div>
	</article>
	<?php
endwhile;

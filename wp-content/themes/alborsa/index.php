<?php
/**
 * Default content template.
 *
 * @package Alborsa
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<div class="site-container content-index">
	<?php if ( have_posts() ) : ?>
		<header class="page-heading">
			<h1><?php esc_html_e( 'أحدث المحتوى', 'alborsa' ); ?></h1>
		</header>
		<div class="content-list">
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<article <?php post_class( 'content-list__item' ); ?>>
					<p class="content-list__meta"><?php echo esc_html( get_the_date() ); ?></p>
					<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<?php the_excerpt(); ?>
				</article>
				<?php
			endwhile;
			?>
		</div>
		<?php the_posts_pagination(); ?>
	<?php else : ?>
		<section class="empty-state" aria-labelledby="empty-state-title">
			<h1 id="empty-state-title"><?php esc_html_e( 'لا يوجد محتوى منشور بعد', 'alborsa' ); ?></h1>
			<p><?php esc_html_e( 'ستظهر هنا الأخبار والتحليلات بعد مراجعتها ونشرها.', 'alborsa' ); ?></p>
		</section>
	<?php endif; ?>
</div>
<?php
get_footer();

<?php
/**
 * Shared archive loop and empty state.
 *
 * @package Alborsa
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$empty_title = isset( $args['empty_title'] ) && is_string( $args['empty_title'] ) ? $args['empty_title'] : __( 'لا يوجد محتوى منشور بعد', 'alborsa' );
$empty_text  = isset( $args['empty_text'] ) && is_string( $args['empty_text'] ) ? $args['empty_text'] : __( 'سيظهر المحتوى هنا بعد مراجعته ونشره.', 'alborsa' );
?>
<?php if ( have_posts() ) : ?>
	<div class="editorial-grid">
		<?php
		while ( have_posts() ) :
			the_post();
			get_template_part(
				'template-parts/content/card',
				null,
				array( 'post' => get_post() )
			);
		endwhile;
		?>
	</div>
	<?php
	the_posts_pagination(
		array(
			'mid_size'  => 1,
			'prev_text' => __( 'السابق', 'alborsa' ),
			'next_text' => __( 'التالي', 'alborsa' ),
		)
	);
	?>
<?php else : ?>
	<section class="empty-state" aria-labelledby="archive-empty-title">
		<h2 id="archive-empty-title"><?php echo esc_html( $empty_title ); ?></h2>
		<p><?php echo esc_html( $empty_text ); ?></p>
	</section>
<?php endif; ?>

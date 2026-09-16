<?php
/**
 * Search results template.
 *
 * @package Alborsa
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<section class="section-hero section-hero--compact">
	<div class="site-container section-hero__inner">
		<p class="eyebrow"><?php esc_html_e( 'البحث', 'alborsa' ); ?></p>
		<h1>
			<?php
			printf(
				/* translators: %s: Search query. */
				esc_html__( 'نتائج: %s', 'alborsa' ),
				esc_html( get_search_query() )
			);
			?>
		</h1>
	</div>
</section>
<div class="site-container section-content">
	<?php
	get_template_part(
		'template-parts/content/archive-loop',
		null,
		array(
			'empty_title' => __( 'لا توجد نتائج مطابقة', 'alborsa' ),
			'empty_text'  => __( 'جرّب كلمة أقصر أو اسم شركة أو موضوعاً مختلفاً.', 'alborsa' ),
		)
	);
	?>
</div>
<?php
get_footer();

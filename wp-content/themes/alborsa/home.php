<?php
/**
 * News index template.
 *
 * @package Alborsa
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
	<?php
	get_template_part(
		'template-parts/content/archive-loop',
		null,
		array(
			'empty_title' => __( 'لا توجد أخبار منشورة بعد', 'alborsa' ),
			'empty_text'  => __( 'ستظهر هنا التغطيات بعد مراجعتها من فريق التحرير.', 'alborsa' ),
		)
	);
	?>
</div>
<?php
get_footer();

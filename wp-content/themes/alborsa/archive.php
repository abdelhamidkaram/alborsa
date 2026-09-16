<?php
/**
 * Editorial archive template.
 *
 * @package Alborsa
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$archive_post_type = (string) get_query_var( 'post_type' );

if ( 'alb_analysis' === $archive_post_type ) {
	$eyebrow      = __( 'قراءة ما وراء الخبر', 'alborsa' );
	$archive_name = __( 'التحليل', 'alborsa' );
	$description  = __( 'قراءات فنية وأساسية تضع حركة السوق في سياق واضح وتفصل بين الحقيقة والافتراض.', 'alborsa' );
	$empty_title  = __( 'لا توجد تحليلات منشورة بعد', 'alborsa' );
	$empty_text   = __( 'ستظهر هنا التحليلات بعد اكتمال مراجعتها التحريرية.', 'alborsa' );
} elseif ( 'alb_guide' === $archive_post_type ) {
	$eyebrow      = __( 'تعلم بخطوات واضحة', 'alborsa' );
	$archive_name = __( 'التعليم', 'alborsa' );
	$description  = __( 'مسارات عربية منظمة من الأساسيات إلى التحليل وإدارة المخاطر والاستثمار المتوافق مع الضوابط الشرعية.', 'alborsa' );
	$empty_title  = __( 'لا توجد دروس منشورة بعد', 'alborsa' );
	$empty_text   = __( 'ستظهر هنا الأدلة التعليمية بعد مراجعتها ونشرها.', 'alborsa' );
} else {
	$eyebrow      = __( 'أرشيف البورصة', 'alborsa' );
	$archive_name = get_the_archive_title();
	$description  = wp_strip_all_tags( get_the_archive_description() );
	$empty_title  = __( 'لا يوجد محتوى في هذا الأرشيف', 'alborsa' );
	$empty_text   = __( 'جرّب العودة لاحقاً أو استكشف بقية أقسام الموقع.', 'alborsa' );
}

get_header();
?>
<section class="section-hero">
	<div class="site-container section-hero__inner">
		<p class="eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
		<h1><?php echo esc_html( $archive_name ); ?></h1>
		<?php if ( '' !== $description ) : ?>
			<p><?php echo esc_html( $description ); ?></p>
		<?php endif; ?>
	</div>
</section>
<div class="site-container section-content">
	<?php if ( 'alb_guide' === $archive_post_type ) : ?>
		<?php get_template_part( 'template-parts/content/education-overview' ); ?>
	<?php endif; ?>
	<?php
	get_template_part(
		'template-parts/content/archive-loop',
		null,
		array(
			'empty_title' => $empty_title,
			'empty_text'  => $empty_text,
		)
	);
	?>
	<?php if ( 'alb_guide' === $archive_post_type ) : ?>
		<section class="education-roadmap" aria-labelledby="education-roadmap-title">
			<div><p class="eyebrow"><?php esc_html_e( 'ما بعد الأدلة', 'alborsa' ); ?></p><h2 id="education-roadmap-title"><?php esc_html_e( 'مكونات تعليمية في الطريق', 'alborsa' ); ?></h2></div>
			<ul class="availability-list">
				<li><strong><?php esc_html_e( 'المسرد المالي العربي', 'alborsa' ); ?></strong><span><?php esc_html_e( 'مصطلحات عربية مع المقابل الإنجليزي؛ مخطط ضمن P1.', 'alborsa' ); ?></span></li>
				<li><strong><?php esc_html_e( 'الفيديو', 'alborsa' ); ?></strong><span><?php esc_html_e( 'دروس عربية مستضافة على يوتيوب بعد اعتماد خطة المحتوى.', 'alborsa' ); ?></span></li>
				<li><strong><?php esc_html_e( 'الاختبارات وتتبع التقدم', 'alborsa' ); ?></strong><span><?php esc_html_e( 'مرتبطة بمرحلة حسابات المستخدمين اللاحقة.', 'alborsa' ); ?></span></li>
			</ul>
		</section>
	<?php endif; ?>
</div>
<?php
get_footer();

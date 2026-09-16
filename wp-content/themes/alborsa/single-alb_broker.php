<?php
// phpcs:ignoreFile WordPress.Files.FileName.NotHyphenatedLowercase -- WordPress requires the registered post-type name in this template filename.
/**
 * Singular broker profile.
 *
 * @package Alborsa
 */

declare(strict_types=1);

use function Alborsa\Theme\Content\term_names;
use function Alborsa\Theme\TemplateTags\section_url;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$broker_id    = get_the_ID();
	$types        = term_names( $broker_id, 'alb_broker_type' );
	$jurisdiction = term_names( $broker_id, 'alb_jurisdiction' );
	?>
	<article <?php post_class( 'broker-profile' ); ?>>
		<header class="broker-profile__header">
			<div class="site-container broker-profile__headline">
				<div>
					<p class="eyebrow"><?php esc_html_e( 'ملف وسيط', 'alborsa' ); ?></p>
					<h1><?php the_title(); ?></h1>
					<?php if ( '' !== $types || '' !== $jurisdiction ) : ?>
						<p><?php echo esc_html( implode( ' — ', array_filter( array( $types, $jurisdiction ) ) ) ); ?></p>
					<?php endif; ?>
				</div>
				<a class="back-link" href="<?php echo esc_url( section_url( 'brokers' ) ); ?>"><?php esc_html_e( 'العودة إلى دليل الوسطاء', 'alborsa' ); ?></a>
			</div>
		</header>

		<div class="site-container broker-profile__layout">
			<div class="broker-profile__main">
				<?php if ( '' !== trim( (string) get_the_content() ) ) : ?>
					<div class="entry-content broker-profile__content"><?php the_content(); ?></div>
				<?php endif; ?>

				<div class="broker-detail" data-alborsa-broker-detail="<?php echo esc_attr( (string) $broker_id ); ?>" aria-live="polite">
					<p><?php esc_html_e( 'جارٍ تحميل البيانات المنشورة…', 'alborsa' ); ?></p>
				</div>
			</div>

			<aside class="content-disclosure broker-profile__disclosure" aria-labelledby="broker-disclosure-title">
				<h2 id="broker-disclosure-title"><?php esc_html_e( 'حالة هذه المراجعة', 'alborsa' ); ?></h2>
				<p><?php esc_html_e( 'يعرض الملف معلومات موثقة بالمصدر وبيانات وصفية منشورة. التقييم التحريري ومنهجية المراجعات الموثقة وروابط العمولة غير معتمدة بعد.', 'alborsa' ); ?></p>
				<p><strong><?php esc_html_e( 'لا يوجد زر فتح حساب تابع في هذا الإصدار.', 'alborsa' ); ?></strong></p>
			</aside>
		</div>
	</article>
	<?php
endwhile;

get_footer();

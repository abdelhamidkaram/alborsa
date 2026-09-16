<?php
/**
 * Not-found template.
 *
 * @package Alborsa
 */

declare(strict_types=1);

use function Alborsa\Theme\TemplateTags\navigation_items;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<section class="not-found">
	<div class="site-container not-found__inner">
		<p class="not-found__code" aria-hidden="true">404</p>
		<div>
			<p class="eyebrow"><?php esc_html_e( 'الرابط غير موجود', 'alborsa' ); ?></p>
			<h1><?php esc_html_e( 'لم نجد الصفحة التي تبحث عنها', 'alborsa' ); ?></h1>
			<p><?php esc_html_e( 'قد يكون الرابط تغير أو لم يُنشر بعد. يمكنك العودة إلى أحد الأقسام الرئيسية.', 'alborsa' ); ?></p>
			<nav aria-label="<?php esc_attr_e( 'روابط بديلة', 'alborsa' ); ?>">
				<?php foreach ( navigation_items() as $navigation_item ) : ?>
					<a href="<?php echo esc_url( $navigation_item['url'] ); ?>"><?php echo esc_html( $navigation_item['label'] ); ?></a>
				<?php endforeach; ?>
			</nav>
		</div>
	</div>
</section>
<?php
get_footer();

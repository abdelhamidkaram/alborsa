<?php
/**
 * Global site footer.
 *
 * @package Alborsa
 */

declare(strict_types=1);

use function Alborsa\Theme\TemplateTags\primary_menu_fallback;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
</main>
<footer class="site-footer" role="contentinfo">
	<div class="site-container site-footer__grid">
		<div class="site-footer__brand">
			<a class="site-wordmark" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
				<span class="site-wordmark__ar"><?php esc_html_e( 'البورصة', 'alborsa' ); ?></span>
				<span class="site-wordmark__latin" aria-hidden="true">alborsa.net</span>
			</a>
			<p><?php esc_html_e( 'معرفة مالية عربية أوضح، موجهة للمستثمر في السعودية والخليج.', 'alborsa' ); ?></p>
		</div>
		<nav class="site-footer__nav" aria-label="<?php esc_attr_e( 'روابط التذييل', 'alborsa' ); ?>">
			<?php if ( has_nav_menu( 'footer' ) ) : ?>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'footer',
						'container'      => false,
						'menu_class'     => 'footer-menu',
						'fallback_cb'    => false,
						'depth'          => 1,
					)
				);
				?>
			<?php else : ?>
				<?php primary_menu_fallback( 'footer-menu' ); ?>
			<?php endif; ?>
		</nav>
		<div class="site-footer__disclosure">
			<h2><?php esc_html_e( 'تنبيه مهم', 'alborsa' ); ?></h2>
			<p><?php esc_html_e( 'المحتوى المنشور لأغراض إعلامية وتعليمية فقط، ولا يُعد نصيحة مالية أو توصية بالشراء أو البيع.', 'alborsa' ); ?></p>
		</div>
	</div>
	<div class="site-container site-footer__legal">
		<p><?php esc_html_e( 'مصادر البيانات ودرجة التأخير ستظهر بوضوح بعد اعتماد مزود السوق.', 'alborsa' ); ?></p>
		<p class="site-footer__copyright">
			<?php
			printf(
				/* translators: %s: Current year. */
				esc_html__( '© %s البورصة. جميع الحقوق محفوظة.', 'alborsa' ),
				esc_html( wp_date( 'Y' ) )
			);
			?>
		</p>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>

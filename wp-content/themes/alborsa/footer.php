<?php
/**
 * Baseline document footer.
 *
 * @package Alborsa
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
</main>
<footer class="site-footer" role="contentinfo">
	<div class="site-container">
		<p><?php esc_html_e( 'المحتوى المنشور لأغراض إعلامية وتعليمية فقط، ولا يُعد نصيحة مالية.', 'alborsa' ); ?></p>
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

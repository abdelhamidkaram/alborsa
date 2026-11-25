<?php
/**
 * Global site footer rebuilt from the approved Figma frame.
 *
 * @package Alborsa
 */

declare(strict_types=1);

use function Alborsa\Theme\TemplateTags\footer_navigation;
use function Alborsa\Theme\TemplateTags\navigation_items;
use function Alborsa\Theme\TemplateTags\news_url;
use function Alborsa\Theme\TemplateTags\section_url;
use function Alborsa\Theme\TemplateTags\site_logo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$navigation    = navigation_items();
$markets_links = array(
	'markets' => $navigation['markets'],
	'tools'   => $navigation['tools'],
	'brokers' => $navigation['brokers'],
);
$learn_links   = array(
	'education' => $navigation['education'],
	'news'      => array(
		'label' => __( 'الأخبار والتحليل', 'alborsa' ),
		'url'   => news_url(),
	),
	'glossary'  => array(
		'label' => __( 'المسرد', 'alborsa' ),
		'url'   => section_url( 'masrad' ),
	),
);
$about_links   = array(
	'about'      => array(
		'label' => __( 'من نحن', 'alborsa' ),
		'url'   => section_url( 'about' ),
	),
	'policy'     => array(
		'label' => __( 'السياسة التحريرية', 'alborsa' ),
		'url'   => section_url( 'editorial-policy' ),
	),
	'disclosure' => array(
		'label' => __( 'الإفصاحات', 'alborsa' ),
		'url'   => section_url( 'disclosures' ),
	),
	'contact'    => array(
		'label' => __( 'تواصل معنا', 'alborsa' ),
		'url'   => section_url( 'contact' ),
	),
);
?>
</main>
<footer id="site-footer" class="site-footer" role="contentinfo">
	<div class="site-footer__risk">
		<div class="site-container site-footer__risk-inner">
			<svg aria-hidden="true" width="18" height="18" viewBox="0 0 18 18" fill="none">
				<circle cx="9" cy="9" r="6.75" stroke="currentColor" stroke-width="1.5" />
				<path d="M9 8.25v4M9 5.75h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
			</svg>
			<p><?php esc_html_e( 'التداول ينطوي على مخاطر قد تفقدك جزءاً من رأس المال، أو كله. المحتوى للمعلومات وليس نصيحة استثمارية، والأرقام في هذه الشاشة تجريبية.', 'alborsa' ); ?></p>
		</div>
	</div>

	<div class="site-container site-footer__main">
		<div class="site-footer__brand">
			<?php site_logo(); ?>
			<p><?php esc_html_e( 'أسواق الخليج وأدوات التداول ومراجعات الوسطاء، بالعربي وللمستثمر الفرد.', 'alborsa' ); ?></p>
		</div>

		<nav class="site-footer__nav" aria-labelledby="footer-markets-title">
			<h2 id="footer-markets-title"><?php esc_html_e( 'الأسواق والأدوات', 'alborsa' ); ?></h2>
			<?php footer_navigation( 'footer_markets', $markets_links ); ?>
		</nav>

		<nav class="site-footer__nav" aria-labelledby="footer-learn-title">
			<h2 id="footer-learn-title"><?php esc_html_e( 'تعلّم', 'alborsa' ); ?></h2>
			<?php footer_navigation( 'footer_learn', $learn_links ); ?>
		</nav>

		<nav class="site-footer__nav" aria-labelledby="footer-about-title">
			<h2 id="footer-about-title"><?php esc_html_e( 'عن البورصة', 'alborsa' ); ?></h2>
			<?php footer_navigation( 'footer_about', $about_links ); ?>
		</nav>
	</div>

	<div class="site-container site-footer__bottom">
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

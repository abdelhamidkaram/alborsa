<?php
/**
 * Global site header and mobile navigation.
 *
 * @package Alborsa
 */

declare(strict_types=1);

use function Alborsa\Theme\TemplateTags\navigation_items;
use function Alborsa\Theme\TemplateTags\primary_menu_fallback;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'انتقل إلى المحتوى', 'alborsa' ); ?></a>
<div class="site-chrome" data-site-chrome>
	<header class="site-header" role="banner">
		<div class="site-container site-header__inner">
			<a class="site-wordmark" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" aria-label="<?php esc_attr_e( 'البورصة — الرئيسية', 'alborsa' ); ?>">
				<span class="site-wordmark__ar"><?php esc_html_e( 'البورصة', 'alborsa' ); ?></span>
				<span class="site-wordmark__mark" aria-hidden="true"><i></i><i></i><i></i></span>
				<span class="site-wordmark__latin" aria-hidden="true">alborsa.net</span>
			</a>

			<button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-navigation" data-menu-toggle>
				<span class="menu-toggle__icon" aria-hidden="true"><i></i><i></i></span>
				<span><?php esc_html_e( 'القائمة', 'alborsa' ); ?></span>
			</button>

			<nav id="primary-navigation" class="primary-navigation" aria-label="<?php esc_attr_e( 'التنقل الرئيسي', 'alborsa' ); ?>" data-primary-navigation>
				<?php if ( has_nav_menu( 'primary' ) ) : ?>
					<?php
					wp_nav_menu(
						array(
							'theme_location' => 'primary',
							'container'      => false,
							'menu_class'     => 'primary-menu',
							'fallback_cb'    => false,
							'depth'          => 1,
						)
					);
					?>
				<?php else : ?>
					<?php primary_menu_fallback(); ?>
				<?php endif; ?>
			</nav>

			<details class="header-search">
				<summary class="header-action">
					<span class="header-action__search" aria-hidden="true"></span>
					<span class="screen-reader-text"><?php esc_html_e( 'فتح البحث', 'alborsa' ); ?></span>
				</summary>
				<form class="header-search__panel" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<label for="header-search-field"><?php esc_html_e( 'ابحث في البورصة', 'alborsa' ); ?></label>
					<div>
						<input id="header-search-field" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'شركة، خبر، تحليل أو دليل', 'alborsa' ); ?>">
						<button type="submit"><?php esc_html_e( 'ابحث', 'alborsa' ); ?></button>
					</div>
				</form>
			</details>
		</div>
	</header>
	<?php get_template_part( 'template-parts/market-status' ); ?>
</div>
<nav class="mobile-dock" aria-label="<?php esc_attr_e( 'الوجهات الرئيسية', 'alborsa' ); ?>">
	<ul>
		<?php foreach ( array_intersect_key( navigation_items(), array_flip( array( 'home', 'markets', 'news', 'tools', 'education' ) ) ) as $key => $navigation_item ) : ?>
			<li>
				<a class="mobile-dock__link mobile-dock__link--<?php echo esc_attr( $key ); ?>" href="<?php echo esc_url( $navigation_item['url'] ); ?>">
					<span aria-hidden="true"></span>
					<?php echo esc_html( $navigation_item['label'] ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
<main id="primary" class="site-main">

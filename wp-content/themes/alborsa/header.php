<?php
/**
 * Global site header rebuilt from the approved Figma chrome.
 *
 * @package Alborsa
 */

declare(strict_types=1);

use function Alborsa\Theme\TemplateTags\navigation_items;
use function Alborsa\Theme\TemplateTags\site_logo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$registered_navigation = navigation_items();
$fallback_navigation   = array();

foreach ( array( 'markets', 'tools', 'brokers', 'news', 'education' ) as $navigation_key ) {
	if ( isset( $registered_navigation[ $navigation_key ] ) ) {
		$fallback_navigation[] = $registered_navigation[ $navigation_key ];
	}
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
	<?php get_template_part( 'template-parts/market-status' ); ?>

	<header class="site-header" role="banner">
		<div class="site-container site-header__inner">
			<div class="site-header__brand">
				<?php site_logo(); ?>
			</div>

			<button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-navigation" data-menu-toggle>
				<svg class="menu-toggle__open" aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none">
					<path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
				</svg>
				<svg class="menu-toggle__close" aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none">
					<path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
				</svg>
				<span class="screen-reader-text" data-menu-toggle-label><?php esc_html_e( 'فتح القائمة الرئيسية', 'alborsa' ); ?></span>
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
					<ul class="primary-menu">
						<?php foreach ( $fallback_navigation as $navigation_item ) : ?>
							<li><a href="<?php echo esc_url( $navigation_item['url'] ); ?>"><?php echo esc_html( $navigation_item['label'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</nav>

			<div class="site-header__actions">
				<button class="theme-toggle" type="button" aria-pressed="false" data-theme-toggle>
					<svg class="theme-toggle__sun" aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none">
						<circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.8" />
						<path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.66 6.34l1.41-1.41" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
					</svg>
					<svg class="theme-toggle__moon" aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none">
						<path d="M20.2 15.5A8.5 8.5 0 0 1 8.5 3.8 8.5 8.5 0 1 0 20.2 15.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
					</svg>
				</button>
				<a class="header-login" href="<?php echo esc_url( wp_login_url() ); ?>"><?php esc_html_e( 'دخول', 'alborsa' ); ?></a>
				<a class="button button--primary button--large header-register" href="<?php echo esc_url( wp_registration_url() ); ?>"><?php esc_html_e( 'أنشئ حساباً', 'alborsa' ); ?></a>
			</div>
		</div>
	</header>
</div>

<?php get_template_part( 'template-parts/mobile-navigation' ); ?>

<main id="primary" class="site-main">

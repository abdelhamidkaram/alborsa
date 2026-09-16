<?php
/**
 * Baseline document header.
 *
 * @package Alborsa
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'انتقل إلى المحتوى', 'alborsa' ); ?></a>
<header class="site-header" role="banner">
	<div class="site-container site-header__inner">
		<a class="site-wordmark" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<span class="site-wordmark__ar"><?php esc_html_e( 'البورصة', 'alborsa' ); ?></span>
			<span class="site-wordmark__latin" aria-hidden="true">alborsa.net</span>
		</a>
	</div>
</header>
<main id="primary" class="site-main">

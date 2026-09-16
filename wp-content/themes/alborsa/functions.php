<?php
/**
 * Theme bootstrap.
 *
 * @package Alborsa
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ALBORSA_THEME_VERSION', '0.1.0' );
define( 'ALBORSA_THEME_PATH', get_template_directory() );
define( 'ALBORSA_THEME_URL', get_template_directory_uri() );

require_once ALBORSA_THEME_PATH . '/inc/setup.php';
require_once ALBORSA_THEME_PATH . '/inc/assets.php';
require_once ALBORSA_THEME_PATH . '/inc/template-tags.php';
require_once ALBORSA_THEME_PATH . '/inc/market-status.php';
require_once ALBORSA_THEME_PATH . '/inc/home-content.php';

Alborsa\Theme\Setup\register_hooks();
Alborsa\Theme\Assets\register_hooks();

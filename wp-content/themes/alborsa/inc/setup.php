<?php
/**
 * Theme setup and supported WordPress features.
 *
 * @package Alborsa
 */

declare(strict_types=1);

namespace Alborsa\Theme\Setup;

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register setup hooks.
 */
function register_hooks(): void {
	add_action( 'after_setup_theme', __NAMESPACE__ . '\\setup' );
	add_action( 'after_setup_theme', __NAMESPACE__ . '\\set_content_width', 0 );

	remove_action( 'wp_head', 'wp_site_icon', 99 );
	remove_action( 'admin_head', 'wp_site_icon' );
	remove_action( 'login_head', 'wp_site_icon', 99 );

	add_action( 'wp_head', __NAMESPACE__ . '\\render_site_icon', 99 );
	add_action( 'admin_head', __NAMESPACE__ . '\\render_site_icon', 99 );
	add_action( 'login_head', __NAMESPACE__ . '\\render_site_icon', 99 );
}

/**
 * Use the official standalone brand mark as the browser icon everywhere.
 */
function render_site_icon(): void {
	?>
	<link rel="icon" href="<?php echo esc_url( get_theme_file_uri( 'assets/images/alborsa-mark.svg' ) ); ?>" type="image/svg+xml">
	<link rel="icon" href="<?php echo esc_url( get_theme_file_uri( 'assets/images/alborsa-mark-dark.svg' ) ); ?>" type="image/svg+xml" media="(prefers-color-scheme: dark)">
	<?php
}

/**
 * Configure supported theme features and menus.
 */
function setup(): void {
	load_theme_textdomain( 'alborsa', ALBORSA_THEME_PATH . '/languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_editor_style( array( 'assets/css/tokens.css', 'assets/css/editor.css' ) );
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
			'navigation-widgets',
		)
	);
	register_nav_menus(
		array(
			'primary'        => __( 'التنقل الرئيسي', 'alborsa' ),
			'footer_markets' => __( 'التذييل: الأسواق والأدوات', 'alborsa' ),
			'footer_learn'   => __( 'التذييل: تعلّم', 'alborsa' ),
			'footer_about'   => __( 'التذييل: عن البورصة', 'alborsa' ),
		)
	);

	add_image_size( 'alborsa-feature', 960, 600, true );
	add_image_size( 'alborsa-card', 640, 400, true );
}

/**
 * Set the default content width used by embeds and media.
 */
function set_content_width(): void {
	$GLOBALS['content_width'] = 760;
}

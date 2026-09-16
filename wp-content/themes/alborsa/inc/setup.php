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
	add_theme_support( 'customize-selective-refresh-widgets' );
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
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 72,
			'width'       => 260,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	register_nav_menus(
		array(
			'primary' => __( 'التنقل الرئيسي', 'alborsa' ),
			'utility' => __( 'التنقل المساعد', 'alborsa' ),
			'footer'  => __( 'روابط التذييل', 'alborsa' ),
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

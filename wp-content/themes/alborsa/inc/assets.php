<?php
/**
 * Frontend asset registration.
 *
 * @package Alborsa
 */

declare(strict_types=1);

namespace Alborsa\Theme\Assets;

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register asset hooks.
 */
function register_hooks(): void {
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_assets' );
}

/**
 * Enqueue the theme stylesheet and design tokens.
 */
function enqueue_assets(): void {
	wp_enqueue_style(
		'alborsa-theme',
		get_stylesheet_uri(),
		array(),
		ALBORSA_THEME_VERSION
	);

	wp_enqueue_style(
		'alborsa-theme-main',
		ALBORSA_THEME_URL . '/assets/css/main.css',
		array( 'alborsa-theme' ),
		asset_version( '/assets/css/main.css' )
	);

	wp_enqueue_script(
		'alborsa-theme-navigation',
		ALBORSA_THEME_URL . '/assets/js/navigation.js',
		array(),
		asset_version( '/assets/js/navigation.js' ),
		array(
			'strategy'  => 'defer',
			'in_footer' => false,
		)
	);
}

/**
 * Return a cache-safe version for a theme asset.
 *
 * @param string $relative_path Root-relative path below the theme directory.
 */
function asset_version( string $relative_path ): string {
	$absolute_path = ALBORSA_THEME_PATH . $relative_path;
	$modified      = file_exists( $absolute_path ) ? filemtime( $absolute_path ) : false;

	return false === $modified ? ALBORSA_THEME_VERSION : (string) $modified;
}

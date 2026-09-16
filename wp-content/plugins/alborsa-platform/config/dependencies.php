<?php
/**
 * Soft dependencies used by diagnostics only.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

return array(
	'polylang'         => array(
		'label'           => 'Polylang',
		'category'        => 'required',
		'plugin_files'    => array( 'polylang/polylang.php', 'polylang-pro/polylang.php' ),
		'runtime_markers' => array(
			array( 'constant', 'POLYLANG_VERSION' ),
			array( 'function', 'PLL' ),
		),
	),
	'yoast-seo'        => array(
		'label'           => 'Yoast SEO',
		'category'        => 'required',
		'plugin_files'    => array( 'wordpress-seo/wp-seo.php', 'wordpress-seo-premium/wp-seo-premium.php' ),
		'runtime_markers' => array(
			array( 'constant', 'WPSEO_VERSION' ),
			array( 'class', 'WPSEO_Options' ),
		),
	),
	'breeze'           => array(
		'label'           => 'Breeze',
		'category'        => 'recommended',
		'plugin_files'    => array( 'breeze/breeze.php' ),
		'runtime_markers' => array(
			array( 'constant', 'BREEZE_VERSION' ),
		),
	),
	'object-cache-pro' => array(
		'label'           => 'Object Cache Pro',
		'category'        => 'recommended',
		'plugin_files'    => array( 'redis-cache-pro/redis-cache-pro.php' ),
		'runtime_markers' => array(
			array( 'class', 'RedisCachePro\\Plugin' ),
		),
	),
	'antispam-bee'     => array(
		'label'           => 'Antispam Bee',
		'category'        => 'optional',
		'plugin_files'    => array( 'antispam-bee/antispam_bee.php' ),
		'runtime_markers' => array(
			array( 'class', 'Antispam_Bee' ),
		),
	),
	'wp-2fa'           => array(
		'label'           => 'WP 2FA',
		'category'        => 'recommended',
		'plugin_files'    => array( 'wp-2fa/wp-2fa.php' ),
		'runtime_markers' => array(
			array( 'constant', 'WP_2FA_VERSION' ),
		),
	),
	'wps-hide-login'   => array(
		'label'           => 'WPS Hide Login',
		'category'        => 'recommended',
		'plugin_files'    => array( 'wps-hide-login/wps-hide-login.php' ),
		'runtime_markers' => array(
			array( 'constant', 'WPS_HIDE_LOGIN_VERSION' ),
			array( 'class', 'WPS_Hide_Login' ),
		),
	),
);

<?php
/**
 * Plugin Name:       Alborsa Platform
 * Plugin URI:        https://alborsa.net/
 * Description:       Product foundation, editorial content, and asset registry for alborsa.net. Run: wp alborsa health.
 * Version:           0.1.0
 * Requires at least: 6.6
 * Requires PHP:      8.1
 * Author:            alborsa.net
 * Text Domain:       alborsa-platform
 * Domain Path:       /languages
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

define( 'ALBORSA_PLATFORM_FILE', __FILE__ );
define( 'ALBORSA_PLATFORM_PATH', plugin_dir_path( __FILE__ ) );

$alborsa_platform_autoloader = ALBORSA_PLATFORM_PATH . 'vendor/autoload.php';

if ( ! is_readable( $alborsa_platform_autoloader ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			echo '<div class="notice notice-error"><p>';
			echo esc_html__( 'Alborsa Platform could not start because its Composer dependencies are missing.', 'alborsa-platform' );
			echo '</p></div>';
		}
	);

	return;
}

require_once $alborsa_platform_autoloader;

register_activation_hook( __FILE__, array( Alborsa\Platform\Foundation\Activation::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Alborsa\Platform\Foundation\Deactivation::class, 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function (): void {
		Alborsa\Platform\Plugin::create()->register();
	},
	20
);

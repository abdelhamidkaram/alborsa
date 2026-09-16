<?php
/**
 * Plugin activation routine.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Foundation;

defined( 'ABSPATH' ) || exit;

final class Activation {
	public static function activate( bool $network_wide = false ): void {
		if ( ! is_multisite() || ! $network_wide ) {
			self::activate_current_site();
			return;
		}

		foreach ( NetworkSiteIds::batches() as $site_ids ) {
			foreach ( $site_ids as $site_id ) {
				switch_to_blog( $site_id );
				self::activate_current_site();
				restore_current_blog();
			}
		}
	}

	private static function activate_current_site(): void {
		$migration_error = MigrationRunner::from_configuration()->run();

		if ( ! is_wp_error( $migration_error ) ) {
			return;
		}

		wp_die(
			esc_html( $migration_error->get_error_message() ),
			esc_html__( 'Alborsa Platform activation failed', 'alborsa-platform' ),
			array( 'back_link' => true )
		);
	}
}

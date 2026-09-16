<?php
/**
 * Plugin deactivation routine.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Foundation;

defined( 'ABSPATH' ) || exit;

final class Deactivation {
	public static function deactivate( bool $network_wide = false ): void {
		if ( ! is_multisite() || ! $network_wide ) {
			delete_option( MigrationRunner::LOCK_OPTION );
			return;
		}

		foreach ( NetworkSiteIds::batches() as $site_ids ) {
			foreach ( $site_ids as $site_id ) {
				switch_to_blog( $site_id );
				delete_option( MigrationRunner::LOCK_OPTION );
				restore_current_blog();
			}
		}
	}
}

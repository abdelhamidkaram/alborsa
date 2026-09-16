<?php
/**
 * Versioned rewrite rule flushing.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Foundation;

defined( 'ABSPATH' ) || exit;

final class RewriteRules {
	public const VERSION_OPTION  = 'alborsa_platform_rewrite_version';
	public const CURRENT_VERSION = 1;

	public function register(): void {
		add_action( 'init', array( $this, 'flush_if_outdated' ), 999 );
	}

	public function flush_if_outdated(): void {
		$installed_version = (int) get_option( self::VERSION_OPTION, 0 );

		if ( self::CURRENT_VERSION <= $installed_version ) {
			return;
		}

		flush_rewrite_rules( false );
		update_option( self::VERSION_OPTION, self::CURRENT_VERSION, false );
	}

	public static function schedule_flush(): void {
		delete_option( self::VERSION_OPTION );
	}
}

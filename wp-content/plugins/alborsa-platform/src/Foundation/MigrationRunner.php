<?php
/**
 * Versioned, repeatable Foundation migrations.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Foundation;

use WP_Error;

defined( 'ABSPATH' ) || exit;

final class MigrationRunner {
	public const LATEST_VERSION = 1;
	public const VERSION_OPTION = 'alborsa_platform_db_version';
	public const LOCK_OPTION    = 'alborsa_platform_migration_lock';

	private const LOCK_TTL = 300;

	private RoleCapabilities $role_capabilities;

	public function __construct( RoleCapabilities $role_capabilities ) {
		$this->role_capabilities = $role_capabilities;
	}

	public static function from_configuration(): self {
		/** @var array<string, list<string>> $role_capabilities */
		$role_capabilities = require dirname( __DIR__, 2 ) . '/config/capabilities.php';

		return new self( new RoleCapabilities( $role_capabilities ) );
	}

	public function installed_version(): int {
		return (int) get_option( self::VERSION_OPTION, 0 );
	}

	public function is_current(): bool {
		return $this->installed_version() === self::LATEST_VERSION;
	}

	public function run(): ?WP_Error {
		$installed_version = $this->installed_version();

		if ( self::LATEST_VERSION < $installed_version ) {
			return new WP_Error(
				'alborsa_platform_schema_newer',
				__( 'The stored Alborsa Platform schema is newer than this plugin version.', 'alborsa-platform' )
			);
		}

		if ( self::LATEST_VERSION === $installed_version ) {
			return null;
		}

		if ( ! $this->acquire_lock() ) {
			return new WP_Error(
				'alborsa_platform_migration_locked',
				__( 'Another Alborsa Platform migration is already running.', 'alborsa-platform' )
			);
		}

		try {
			return $this->migrate_from( $installed_version );
		} finally {
			delete_option( self::LOCK_OPTION );
		}
	}

	private function migrate_from( int $installed_version ): ?WP_Error {
		for ( $version = $installed_version + 1; $version <= self::LATEST_VERSION; ++$version ) {
			$migration_error = $this->migrate_to( $version );

			if ( is_wp_error( $migration_error ) ) {
				return $migration_error;
			}

			if ( ! $this->store_version( $version ) ) {
				return new WP_Error(
					'alborsa_platform_version_write_failed',
					__( 'Alborsa Platform could not store its migration version.', 'alborsa-platform' )
				);
			}
		}

		return null;
	}

	private function migrate_to( int $version ): ?WP_Error {
		if ( 1 === $version && ! $this->role_capabilities->install() ) {
			return new WP_Error(
				'alborsa_platform_role_missing',
				__( 'Alborsa Platform could not initialize the administrator role.', 'alborsa-platform' )
			);
		}

		return null;
	}

	private function store_version( int $version ): bool {
		if ( update_option( self::VERSION_OPTION, $version, false ) ) {
			return true;
		}

		return $version === $this->installed_version();
	}

	private function acquire_lock(): bool {
		if ( add_option( self::LOCK_OPTION, time(), '', false ) ) {
			return true;
		}

		$lock_created_at = (int) get_option( self::LOCK_OPTION, 0 );

		if ( $lock_created_at > time() - self::LOCK_TTL ) {
			return false;
		}

		delete_option( self::LOCK_OPTION );

		return add_option( self::LOCK_OPTION, time(), '', false );
	}
}

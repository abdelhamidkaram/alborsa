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
	public const LATEST_VERSION      = 4;
	public const VERSION_OPTION      = 'alborsa_platform_db_version';
	public const LOCK_OPTION         = 'alborsa_platform_migration_lock';
	public const RELATIONSHIPS_TABLE = 'alb_relationships';
	public const MARKET_QUOTES_TABLE = 'alb_market_quotes';

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

	/**
	 * SQL statement for creating or updating the relationships table with dbDelta.
	 *
	 * @param string $table_name Full table name including prefix.
	 * @param string $charset_collate Optional charset collate clause from wpdb.
	 * @return string dbDelta-compatible CREATE TABLE statement.
	 */
	public static function relationships_table_schema( string $table_name, string $charset_collate = '' ): string {
		$charset_collate_clause = '' !== $charset_collate ? ' ' . $charset_collate : '';

		return "CREATE TABLE {$table_name} (\n"
			. "  id bigint(20) unsigned NOT NULL auto_increment,\n"
			. "  from_object_id bigint(20) unsigned NOT NULL,\n"
			. "  from_object_type varchar(32) NOT NULL,\n"
			. "  to_object_id bigint(20) unsigned NOT NULL,\n"
			. "  to_object_type varchar(32) NOT NULL,\n"
			. "  relation_type varchar(32) NOT NULL,\n"
			. "  sort_order int(11) NOT NULL default 0,\n"
			. "  created_at datetime NOT NULL,\n"
			. "  PRIMARY KEY  (id),\n"
			. "  UNIQUE KEY uq_relationship (from_object_id, from_object_type, to_object_id, to_object_type, relation_type),\n"
			. "  KEY ix_target_lookup (to_object_id, to_object_type, relation_type)\n"
			. "){$charset_collate_clause};";
	}

	/**
	 * SQL statement for creating or updating the latest market quotes table.
	 *
	 * @param string $table_name Full table name including prefix.
	 * @param string $charset_collate Optional charset collate clause from wpdb.
	 * @return string dbDelta-compatible CREATE TABLE statement.
	 */
	public static function market_quotes_table_schema( string $table_name, string $charset_collate = '' ): string {
		$charset_collate_clause = '' !== $charset_collate ? ' ' . $charset_collate : '';

		return "CREATE TABLE {$table_name} (\n"
			. "  id bigint(20) unsigned NOT NULL auto_increment,\n"
			. "  asset_id bigint(20) unsigned NOT NULL,\n"
			. "  provider_key varchar(32) NOT NULL,\n"
			. "  provider_symbol varchar(64) NOT NULL,\n"
			. "  price decimal(30,10) NULL,\n"
			. "  open_price decimal(30,10) NULL,\n"
			. "  previous_close decimal(30,10) NULL,\n"
			. "  high_price decimal(30,10) NULL,\n"
			. "  low_price decimal(30,10) NULL,\n"
			. "  volume decimal(30,10) NULL,\n"
			. "  absolute_change decimal(30,10) NULL,\n"
			. "  percent_change decimal(30,10) NULL,\n"
			. "  currency char(3) NULL,\n"
			. "  market_timestamp datetime NOT NULL,\n"
			. "  received_at datetime NOT NULL,\n"
			. "  delay_seconds int(10) unsigned NOT NULL default 0,\n"
			. "  status varchar(16) NOT NULL,\n"
			. "  payload_hash char(64) NOT NULL,\n"
			. "  PRIMARY KEY  (id),\n"
			. "  UNIQUE KEY uq_asset_provider (asset_id, provider_key),\n"
			. "  KEY ix_provider_symbol (provider_key, provider_symbol),\n"
			. "  KEY ix_status_received (status, received_at)\n"
			. "){$charset_collate_clause};";
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
		if ( in_array( $version, array( 1, 3 ), true ) && ! $this->role_capabilities->install() ) {
			return new WP_Error(
				'alborsa_platform_role_missing',
				__( 'Alborsa Platform could not initialize the required WordPress role capabilities.', 'alborsa-platform' )
			);
		}

		if ( 2 === $version ) {
			return $this->migrate_v2_relationships_table();
		}

		if ( 4 === $version ) {
			return $this->migrate_v4_market_quotes_table();
		}

		return null;
	}

	private function migrate_v2_relationships_table(): ?WP_Error {
		return $this->migrate_table( self::RELATIONSHIPS_TABLE, array( self::class, 'relationships_table_schema' ) );
	}

	private function migrate_v4_market_quotes_table(): ?WP_Error {
		return $this->migrate_table( self::MARKET_QUOTES_TABLE, array( self::class, 'market_quotes_table_schema' ) );
	}

	/**
	 * @param callable(string, string): string $schema_callback Builds dbDelta SQL.
	 */
	private function migrate_table( string $table_suffix, callable $schema_callback ): ?WP_Error {
		global $wpdb;

		if ( ! $wpdb instanceof \wpdb ) {
			return new WP_Error(
				'alborsa_platform_db_unavailable',
				__( 'WordPress database connection is not available.', 'alborsa-platform' )
			);
		}

		if ( ! function_exists( 'dbDelta' ) ) {
			$upgrade_file = ABSPATH . 'wp-admin/includes/upgrade.php';

			if ( file_exists( $upgrade_file ) ) {
				require_once $upgrade_file;
			}
		}

		if ( ! function_exists( 'dbDelta' ) ) {
			return new WP_Error(
				'alborsa_platform_dbdelta_missing',
				__( 'WordPress upgrade API (dbDelta) is not available.', 'alborsa-platform' )
			);
		}

		$table_name      = $wpdb->prefix . $table_suffix;
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = $schema_callback( $table_name, $charset_collate );

		dbDelta( $sql );

		if ( ! $this->table_exists( $table_name, $wpdb ) ) {
			$error_detail = ! empty( $wpdb->last_error ) ? ' ' . $wpdb->last_error : '';

			return new WP_Error(
				'alborsa_platform_table_creation_failed',
				sprintf(
					/* translators: 1: table name, 2: database error details. */
					__( 'Alborsa Platform could not create table %1$s.%2$s', 'alborsa-platform' ),
					$table_name,
					$error_detail
				)
			);
		}

		return null;
	}

	private function table_exists( string $table_name, \wpdb $wpdb ): bool {
		$found = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) )
		);

		return $found === $table_name;
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

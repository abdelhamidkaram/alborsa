<?php
/**
 * Foundation migration tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Foundation;

use Alborsa\Platform\Foundation\MigrationRunner;
use Alborsa\Platform\Foundation\RoleCapabilities;
use Alborsa\Platform\Tests\Support\WpdbDouble;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;

final class MigrationRunnerTest extends UnitTestCase {
	public function test_schema_sql_contains_expected_columns_keys_and_formatting(): void {
		$table_name      = 'wp_alb_relationships';
		$charset_collate = 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
		$sql             = MigrationRunner::relationships_table_schema( $table_name, $charset_collate );

		self::assertStringContainsString( 'CREATE TABLE wp_alb_relationships', $sql );
		self::assertStringContainsString( 'id bigint(20) unsigned NOT NULL auto_increment', $sql );
		self::assertStringContainsString( 'from_object_id bigint(20) unsigned NOT NULL', $sql );
		self::assertStringContainsString( 'from_object_type varchar(32) NOT NULL', $sql );
		self::assertStringContainsString( 'to_object_id bigint(20) unsigned NOT NULL', $sql );
		self::assertStringContainsString( 'to_object_type varchar(32) NOT NULL', $sql );
		self::assertStringContainsString( 'relation_type varchar(32) NOT NULL', $sql );
		self::assertStringContainsString( 'sort_order int(11) NOT NULL default 0', $sql );
		self::assertStringContainsString( 'created_at datetime NOT NULL', $sql );
		self::assertStringContainsString( 'PRIMARY KEY  (id)', $sql );
		self::assertStringContainsString( 'UNIQUE KEY uq_relationship (from_object_id, from_object_type, to_object_id, to_object_type, relation_type)', $sql );
		self::assertStringContainsString( 'KEY ix_target_lookup (to_object_id, to_object_type, relation_type)', $sql );
		self::assertStringContainsString( $charset_collate, $sql );
		self::assertStringNotContainsString( 'FOREIGN KEY', $sql );
	}

	public function test_repeat_run_stores_both_migrations_once_and_is_idempotent(): void {
		$options        = array();
		$version_writes = 0;
		$this->mock_options( $options, $version_writes );

		Functions\when( 'dbDelta' )->justReturn( array() );

		$this->set_wpdb_for_test( $this->create_stub_wpdb( 'wp_alb_relationships' ) );
		$runner = new MigrationRunner( new RoleCapabilities( array() ) );

		self::assertNull( $runner->run() );
		self::assertNull( $runner->run() );
		self::assertSame( MigrationRunner::LATEST_VERSION, $options[ MigrationRunner::VERSION_OPTION ] );
		self::assertSame( 2, $version_writes );
		self::assertArrayNotHasKey( MigrationRunner::LOCK_OPTION, $options );
	}

	public function test_run_from_v1_advances_to_v2_and_stores_version(): void {
		$options        = array( MigrationRunner::VERSION_OPTION => 1 );
		$version_writes = 0;
		$this->mock_options( $options, $version_writes );

		$executed_sql = '';
		Functions\when( 'dbDelta' )->alias(
			static function ( string $sql ) use ( &$executed_sql ): array {
				$executed_sql = $sql;
				return array();
			}
		);

		$this->set_wpdb_for_test( $this->create_stub_wpdb( 'wp_alb_relationships' ) );
		$runner = new MigrationRunner( new RoleCapabilities( array() ) );

		self::assertNull( $runner->run() );
		self::assertStringContainsString( 'CREATE TABLE wp_alb_relationships', $executed_sql );
		self::assertSame( 2, $options[ MigrationRunner::VERSION_OPTION ] );
		self::assertSame( 1, $version_writes );
	}

	public function test_table_creation_failure_returns_actionable_error_and_does_not_advance_version(): void {
		$options        = array( MigrationRunner::VERSION_OPTION => 1 );
		$version_writes = 0;
		$this->mock_options( $options, $version_writes );

		Functions\when( 'dbDelta' )->justReturn( array() );

		$wpdb             = $this->create_stub_wpdb( null );
		$wpdb->last_error = 'Table creation simulated error';
		$this->set_wpdb_for_test( $wpdb );

		$runner          = new MigrationRunner( new RoleCapabilities( array() ) );
		$migration_error = $runner->run();

		self::assertTrue( is_wp_error( $migration_error ) );
		self::assertSame( 'alborsa_platform_table_creation_failed', $migration_error->get_error_code() );
		self::assertStringContainsString( 'wp_alb_relationships', $migration_error->get_error_message() );
		self::assertStringContainsString( 'Table creation simulated error', $migration_error->get_error_message() );
		self::assertStringContainsString( 'SHOW TABLES LIKE', (string) $wpdb->last_query );
		self::assertSame( 1, $options[ MigrationRunner::VERSION_OPTION ] );
		self::assertSame( 0, $version_writes );
		self::assertArrayNotHasKey( MigrationRunner::LOCK_OPTION, $options );
	}

	public function test_run_returns_error_when_database_connection_is_unavailable(): void {
		$options        = array( MigrationRunner::VERSION_OPTION => 1 );
		$version_writes = 0;
		$this->mock_options( $options, $version_writes );

		$this->set_wpdb_for_test( null );
		$runner          = new MigrationRunner( new RoleCapabilities( array() ) );
		$migration_error = $runner->run();

		self::assertTrue( is_wp_error( $migration_error ) );
		self::assertSame( 'alborsa_platform_db_unavailable', $migration_error->get_error_code() );
		self::assertSame( 1, $options[ MigrationRunner::VERSION_OPTION ] );
		self::assertSame( 0, $version_writes );
		self::assertArrayNotHasKey( MigrationRunner::LOCK_OPTION, $options );
	}

	/**
	 * Mocks WordPress option persistence in memory for isolated migration testing.
	 *
	 * @param array<string, mixed> $options Stored options storage by reference.
	 * @param int                  $version_writes Stored version writes counter by reference.
	 */
	private function mock_options( array &$options, int &$version_writes ): void {
		Functions\when( 'get_option' )->alias(
			static function ( string $key, mixed $fallback = false ) use ( &$options ): mixed {
				return $options[ $key ] ?? $fallback;
			}
		);
		Functions\when( 'add_option' )->alias(
			static function ( string $key, mixed $value ) use ( &$options ): bool {
				if ( array_key_exists( $key, $options ) ) {
					return false;
				}

				$options[ $key ] = $value;
				return true;
			}
		);
		Functions\when( 'update_option' )->alias(
			static function ( string $key, mixed $value ) use ( &$options, &$version_writes ): bool {
				$options[ $key ] = $value;
				if ( MigrationRunner::VERSION_OPTION === $key ) {
					++$version_writes;
				}
				return true;
			}
		);
		Functions\when( 'delete_option' )->alias(
			static function ( string $key ) use ( &$options ): bool {
				unset( $options[ $key ] );
				return true;
			}
		);
	}

	/**
	 * Creates a lightweight wpdb stub for isolated schema migration testing.
	 *
	 * @param string|null $existing_table Simulated table returned by SHOW TABLES LIKE.
	 * @return WpdbDouble
	 */
	private function create_stub_wpdb( ?string $existing_table ): WpdbDouble {
		return new class( $existing_table ) extends WpdbDouble {
			private ?string $existing_table;

			public function __construct( ?string $existing_table ) {
				$this->prefix         = 'wp_';
				$this->existing_table = $existing_table;
			}

			public function get_var( ?string $query = null, int $x = 0, int $y = 0 ): mixed {
				parent::get_var( $query, $x, $y );

				return $this->existing_table;
			}
		};
	}
}

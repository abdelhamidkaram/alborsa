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
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;

final class MigrationRunnerTest extends UnitTestCase {
	public function test_repeated_run_applies_the_migration_once(): void {
		$options        = array();
		$version_writes = 0;

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
				++$version_writes;
				return true;
			}
		);
		Functions\when( 'delete_option' )->alias(
			static function ( string $key ) use ( &$options ): bool {
				unset( $options[ $key ] );
				return true;
			}
		);

		$runner = new MigrationRunner( new RoleCapabilities( array() ) );

		self::assertNull( $runner->run() );
		self::assertNull( $runner->run() );
		self::assertSame( MigrationRunner::LATEST_VERSION, $options[ MigrationRunner::VERSION_OPTION ] );
		self::assertSame( 1, $version_writes );
		self::assertArrayNotHasKey( MigrationRunner::LOCK_OPTION, $options );
	}
}

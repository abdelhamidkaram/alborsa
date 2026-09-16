<?php
/**
 * Foundation module lifecycle tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Foundation;

use Alborsa\Platform\Foundation\FoundationModule;
use Alborsa\Platform\Foundation\MigrationRunner;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;

final class FoundationModuleTest extends UnitTestCase {
	public function test_registration_defers_initialization_until_init(): void {
		Functions\expect( 'load_plugin_textdomain' )->never();

		$module = new FoundationModule();
		$module->register();

		self::assertSame( 0, has_action( 'init', FoundationModule::class . '->initialize()' ) );
	}

	public function test_initialization_loads_translations_before_running_migrations(): void {
		$lifecycle_events = array();

		Functions\when( 'plugin_basename' )->justReturn( 'alborsa-platform/alborsa-platform.php' );
		Functions\when( 'load_plugin_textdomain' )->alias(
			static function () use ( &$lifecycle_events ): bool {
				$lifecycle_events[] = 'translations';
				return true;
			}
		);
		Functions\when( 'get_option' )->alias(
			static function ( string $key, mixed $fallback = false ) use ( &$lifecycle_events ): mixed {
				$lifecycle_events[] = 'migration';

				return MigrationRunner::VERSION_OPTION === $key ? MigrationRunner::LATEST_VERSION : $fallback;
			}
		);

		( new FoundationModule() )->initialize();

		self::assertSame( array( 'translations', 'migration' ), $lifecycle_events );
	}
}

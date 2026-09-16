<?php
/**
 * Plugin activation tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Foundation;

use Alborsa\Platform\Foundation\Activation;
use Alborsa\Platform\Foundation\MigrationRunner;
use Alborsa\Platform\Foundation\RewriteRules;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;

final class ActivationTest extends UnitTestCase {
	public function test_single_site_activation_schedules_one_rewrite_flush(): void {
		$deleted_option = '';

		Functions\when( 'is_multisite' )->justReturn( false );
		Functions\when( 'get_option' )->justReturn( MigrationRunner::LATEST_VERSION );
		Functions\when( 'delete_option' )->alias(
			static function ( string $key ) use ( &$deleted_option ): bool {
				$deleted_option = $key;
				return true;
			}
		);

		Activation::activate();

		self::assertSame( RewriteRules::VERSION_OPTION, $deleted_option );
	}

	public function test_network_activation_schedules_rewrite_flush_for_each_site(): void {
		$lifecycle_events = array();

		Functions\when( 'is_multisite' )->justReturn( true );
		Functions\when( 'get_sites' )->justReturn( array( 2, 3 ) );
		Functions\when( 'get_option' )->justReturn( MigrationRunner::LATEST_VERSION );
		Functions\when( 'switch_to_blog' )->alias(
			static function ( int $site_id ) use ( &$lifecycle_events ): void {
				$lifecycle_events[] = 'switch:' . $site_id;
			}
		);
		Functions\when( 'delete_option' )->alias(
			static function ( string $key ) use ( &$lifecycle_events ): bool {
				$lifecycle_events[] = 'schedule:' . $key;
				return true;
			}
		);
		Functions\when( 'restore_current_blog' )->alias(
			static function () use ( &$lifecycle_events ): void {
				$lifecycle_events[] = 'restore';
			}
		);

		Activation::activate( true );

		self::assertSame(
			array(
				'switch:2',
				'schedule:' . RewriteRules::VERSION_OPTION,
				'restore',
				'switch:3',
				'schedule:' . RewriteRules::VERSION_OPTION,
				'restore',
			),
			$lifecycle_events
		);
	}
}

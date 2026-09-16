<?php
/**
 * Rewrite rule lifecycle tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Foundation;

use Alborsa\Platform\Foundation\RewriteRules;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;

final class RewriteRulesTest extends UnitTestCase {
	public function test_outdated_rules_are_soft_flushed_and_versioned(): void {
		$lifecycle_events = array();

		Functions\when( 'get_option' )->justReturn( 0 );
		Functions\when( 'flush_rewrite_rules' )->alias(
			static function ( bool $hard ) use ( &$lifecycle_events ): void {
				$lifecycle_events[] = $hard ? 'hard-flush' : 'soft-flush';
			}
		);
		Functions\when( 'update_option' )->alias(
			static function ( string $key, int $version, bool $autoload ) use ( &$lifecycle_events ): bool {
				$lifecycle_events[] = sprintf( '%s:%d:%s', $key, $version, $autoload ? 'autoload' : 'no-autoload' );
				return true;
			}
		);

		( new RewriteRules() )->flush_if_outdated();

		self::assertSame(
			array(
				'soft-flush',
				RewriteRules::VERSION_OPTION . ':' . RewriteRules::CURRENT_VERSION . ':no-autoload',
			),
			$lifecycle_events
		);
	}

	public function test_current_rules_are_not_flushed_again(): void {
		$lifecycle_events = array();

		Functions\when( 'get_option' )->justReturn( RewriteRules::CURRENT_VERSION );
		Functions\when( 'flush_rewrite_rules' )->alias(
			static function () use ( &$lifecycle_events ): void {
				$lifecycle_events[] = 'flush';
			}
		);
		Functions\when( 'update_option' )->alias(
			static function () use ( &$lifecycle_events ): bool {
				$lifecycle_events[] = 'update';
				return true;
			}
		);

		( new RewriteRules() )->flush_if_outdated();

		self::assertSame( array(), $lifecycle_events );
	}
}

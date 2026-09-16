<?php
/**
 * Bounded administrative audit log tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Foundation;

use Alborsa\Platform\Foundation\AuditLog;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;

final class AuditLogTest extends UnitTestCase {
	public function test_record_prepends_a_value_free_entry_and_bounds_storage(): void {
		$stored   = array();
		$existing = array_fill( 0, 200, array( 'action' => 'old' ) );
		Functions\when( 'sanitize_key' )->alias( static fn( string $value ): string => strtolower( $value ) );
		Functions\when( 'get_option' )->justReturn( $existing );
		Functions\when( 'get_current_user_id' )->justReturn( 7 );
		Functions\when( 'current_time' )->justReturn( '2026-09-16 12:00:00' );
		Functions\when( 'update_option' )->alias(
			static function ( string $option, array $entries, bool $autoload ) use ( &$stored ): bool {
				$stored = array(
					'option'   => $option,
					'entries'  => $entries,
					'autoload' => $autoload,
				);

				return true;
			}
		);

		( new AuditLog() )->record( 'Broker_Profile_Updated', 'alb_broker', 55 );

		self::assertSame( AuditLog::OPTION, $stored['option'] );
		self::assertFalse( $stored['autoload'] );
		self::assertCount( 200, $stored['entries'] );
		self::assertSame(
			array(
				'action'      => 'broker_profile_updated',
				'object_type' => 'alb_broker',
				'object_id'   => 55,
				'actor_id'    => 7,
				'occurred_at' => '2026-09-16 12:00:00',
			),
			$stored['entries'][0]
		);
	}
}

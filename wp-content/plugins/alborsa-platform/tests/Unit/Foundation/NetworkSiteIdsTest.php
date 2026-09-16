<?php
/**
 * Multisite lifecycle batching tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Foundation;

use Alborsa\Platform\Foundation\NetworkSiteIds;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;

final class NetworkSiteIdsTest extends UnitTestCase {
	public function test_large_network_is_read_in_bounded_batches(): void {
		Functions\when( 'get_sites' )->alias(
			static function ( array $query ): array {
				if ( 0 === $query['offset'] ) {
					return range( 1, 100 );
				}

				return array( 101 );
			}
		);

		$batches = iterator_to_array( NetworkSiteIds::batches(), false );

		self::assertCount( 2, $batches );
		self::assertSame( range( 1, 100 ), $batches[0] );
		self::assertSame( array( 101 ), $batches[1] );
	}
}

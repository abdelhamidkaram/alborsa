<?php
/**
 * Batched multisite identifiers for lifecycle operations.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Foundation;

use Generator;

defined( 'ABSPATH' ) || exit;

final class NetworkSiteIds {
	private const BATCH_SIZE = 100;

	/**
	 * @return Generator<int, list<int>>
	 */
	public static function batches(): Generator {
		$offset = 0;

		do {
			$site_ids       = get_sites(
				array(
					'fields'  => 'ids',
					'number'  => self::BATCH_SIZE,
					'offset'  => $offset,
					'orderby' => 'id',
					'order'   => 'ASC',
				)
			);
			$site_ids       = array_values( array_map( 'intval', $site_ids ) );
			$received_count = count( $site_ids );

			if ( array() !== $site_ids ) {
				yield $site_ids;
			}

			$offset += self::BATCH_SIZE;
		} while ( self::BATCH_SIZE === $received_count );
	}
}

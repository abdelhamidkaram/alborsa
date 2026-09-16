<?php
/**
 * Reads protected quote mappings from financial assets.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\MarketData;

use Alborsa\Platform\Modules\Assets\AssetMeta;
use Alborsa\Platform\Modules\Assets\AssetsModule;

defined( 'ABSPATH' ) || exit;

final class AssetQuoteMappingRepository {
	private const MAX_SYNC_ASSETS = 100;

	public function find_for_asset( int $asset_id ): ?QuoteMapping {
		if ( $asset_id <= 0 ) {
			return null;
		}

		foreach ( $this->stored_mappings( $asset_id ) as $stored_mapping ) {
			$mapping = QuoteMapping::from_array( $stored_mapping );

			if ( null !== $mapping ) {
				return $mapping;
			}
		}

		return null;
	}

	/**
	 * Streams bounded synchronization batches across every published asset.
	 *
	 * @return iterable<int, list<array{asset_id: int, mapping: QuoteMapping}>>
	 */
	public function batches_for_provider( string $provider_key ): iterable {
		if ( ! preg_match( '/\A[a-z0-9_-]{1,32}\z/', $provider_key ) ) {
			return;
		}

		$page = 1;

		do {
			$asset_ids   = $this->published_asset_ids( $page );
			$mappings    = $this->matching_mappings( $asset_ids, $provider_key );
			$asset_count = count( $asset_ids );

			if ( array() !== $mappings ) {
				yield $mappings;
			}

			++$page;
		} while ( self::MAX_SYNC_ASSETS === $asset_count );
	}

	/** @return list<int> */
	private function published_asset_ids( int $page ): array {
		$asset_ids = get_posts(
			array(
				'post_type'              => AssetsModule::POST_TYPE,
				'post_status'            => 'publish',
				'fields'                 => 'ids',
				'posts_per_page'         => self::MAX_SYNC_ASSETS,
				'paged'                  => max( 1, $page ),
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'suppress_filters'       => false,
				'lang'                   => '',
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
			)
		);

		return array_values( $asset_ids );
	}

	/**
	 * @param list<int> $asset_ids Published asset IDs.
	 * @return list<array{asset_id: int, mapping: QuoteMapping}>
	 */
	private function matching_mappings( array $asset_ids, string $provider_key ): array {
		$mappings = array();

		foreach ( $asset_ids as $asset_id ) {
			foreach ( $this->stored_mappings( $asset_id ) as $stored_mapping ) {
				$mapping = QuoteMapping::from_array( $stored_mapping );

				if ( null === $mapping || $provider_key !== $mapping->provider_key() ) {
					continue;
				}

				$mappings[] = array(
					'asset_id' => $asset_id,
					'mapping'  => $mapping,
				);
				break;
			}
		}

		return $mappings;
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private function stored_mappings( int $asset_id ): array {
		$mappings = get_post_meta( $asset_id, AssetMeta::PROVIDER_MAPPINGS, true );

		if ( ! is_array( $mappings ) ) {
			return array();
		}

		return array_values(
			array_filter(
				$mappings,
				static fn( mixed $mapping ): bool => is_array( $mapping )
			)
		);
	}
}

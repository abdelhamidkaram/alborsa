<?php
/**
 * Asset quote mapping repository tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\MarketData;

use Alborsa\Platform\Modules\Assets\AssetMeta;
use Alborsa\Platform\Modules\Assets\AssetsModule;
use Alborsa\Platform\Modules\MarketData\AssetQuoteMappingRepository;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;

final class AssetQuoteMappingRepositoryTest extends UnitTestCase {
	public function test_reader_selects_first_enabled_quote_mapping_without_exposing_other_capabilities(): void {
		Functions\when( 'get_post_meta' )->justReturn(
			array(
				$this->mapping( array( 'enabled' => false ) ),
				$this->mapping( array( 'data_capabilities' => array( 'reference_data' ) ) ),
				$this->mapping(),
			)
		);

		$mapping = ( new AssetQuoteMappingRepository() )->find_for_asset( 20 );

		self::assertNotNull( $mapping );
		self::assertSame( 'licensed-feed', $mapping->provider_key() );
		self::assertSame( '2222.SR', $mapping->provider_symbol() );
	}

	public function test_provider_batch_contains_only_published_assets_with_matching_enabled_mapping(): void {
		$query = array();
		Functions\when( 'get_posts' )->alias(
			static function ( array $arguments ) use ( &$query ): array {
				$query = $arguments;

				return array( 20, 21 );
			}
		);
		Functions\when( 'get_post_meta' )->alias(
			fn( int $asset_id, string $meta_key ): array => AssetMeta::PROVIDER_MAPPINGS === $meta_key
				? array( $this->mapping( array( 'provider_key' => 20 === $asset_id ? 'licensed-feed' : 'other-feed' ) ) )
				: array()
		);

		$batches  = iterator_to_array( ( new AssetQuoteMappingRepository() )->batches_for_provider( 'licensed-feed' ) );
		$mappings = $batches[0];

		self::assertSame( AssetsModule::POST_TYPE, $query['post_type'] );
		self::assertSame( 'publish', $query['post_status'] );
		self::assertSame( 100, $query['posts_per_page'] );
		self::assertSame( 1, $query['paged'] );
		self::assertSame( '', $query['lang'] );
		self::assertTrue( $query['update_post_meta_cache'] );
		self::assertFalse( $query['update_post_term_cache'] );
		self::assertCount( 1, $mappings );
		self::assertSame( 20, $mappings[0]['asset_id'] );
	}

	public function test_provider_batches_continue_after_a_full_page_without_matching_mappings(): void {
		$requested_pages = array();
		Functions\when( 'get_posts' )->alias(
			static function ( array $arguments ) use ( &$requested_pages ): array {
				$requested_pages[] = $arguments['paged'];

				return 1 === $arguments['paged'] ? range( 1, 100 ) : array( 101 );
			}
		);
		Functions\when( 'get_post_meta' )->alias(
			fn( int $asset_id ): array => 101 === $asset_id ? array( $this->mapping() ) : array()
		);

		$batches = iterator_to_array( ( new AssetQuoteMappingRepository() )->batches_for_provider( 'licensed-feed' ) );

		self::assertSame( array( 1, 2 ), $requested_pages );
		self::assertCount( 1, $batches );
		self::assertSame( 101, $batches[0][0]['asset_id'] );
	}

	/**
	 * @param array<string, mixed> $overrides Mapping overrides.
	 * @return array<string, mixed>
	 */
	private function mapping( array $overrides = array() ): array {
		return array_merge(
			array(
				'provider_key'       => 'licensed-feed',
				'provider_symbol'    => '2222.SR',
				'data_capabilities'  => array( 'quotes' ),
				'data_delay_seconds' => 900,
				'attribution_label'  => 'Licensed Feed',
				'attribution_url'    => 'https://example.test/source',
				'enabled'            => true,
			),
			$overrides
		);
	}
}

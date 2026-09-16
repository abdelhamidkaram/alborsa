<?php
/**
 * Public quote read service tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\MarketData;

use Alborsa\Platform\Modules\MarketData\AssetQuoteMappingRepository;
use Alborsa\Platform\Modules\MarketData\ProviderHealthStore;
use Alborsa\Platform\Modules\MarketData\QuoteCache;
use Alborsa\Platform\Modules\MarketData\QuoteReadService;
use Alborsa\Platform\Modules\MarketData\QuoteRepository;
use Alborsa\Platform\Tests\Support\WpdbDouble;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;

final class QuoteReadServiceTest extends UnitTestCase {
	public function test_provider_failure_after_last_snapshot_returns_last_value_as_stale(): void {
		Functions\when( 'get_post_meta' )->justReturn( array( $this->mapping() ) );
		Functions\when( 'wp_cache_get' )->justReturn( false );
		Functions\when( 'wp_cache_set' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn(
			array(
				'licensed-feed' => array(
					'last_failure_at' => '2026-09-16 10:45:00',
				),
			)
		);
		$wpdb = new class() extends WpdbDouble {
			public function get_results( string $query, string|int $output = 'OBJECT' ): ?array {
				parent::get_results( $query, $output );

				return array(
					array(
						'asset_id'         => '20',
						'provider_key'     => 'licensed-feed',
						'provider_symbol'  => '2222.SR',
						'price'            => '30.1000000000',
						'open_price'       => '29.5000000000',
						'previous_close'   => '29.7000000000',
						'high_price'       => '30.5000000000',
						'low_price'        => '29.4000000000',
						'volume'           => '1000000.0000000000',
						'absolute_change'  => '0.4000000000',
						'percent_change'   => '1.3468000000',
						'currency'         => 'SAR',
						'market_timestamp' => '2026-09-16 10:30:00',
						'received_at'      => '2026-09-16 10:31:00',
						'delay_seconds'    => '900',
						'status'           => 'delayed',
						'payload_hash'     => str_repeat( 'a', 64 ),
					),
				);
			}
		};

		$result = $this->service( $wpdb )->find_for_asset( 20 );

		self::assertFalse( is_wp_error( $result ) );
		$data = $result->data();
		self::assertSame( '30.1000000000', $data['price'] );
		self::assertSame( 'stale', $data['status'] );
		self::assertTrue( $data['stale'] );
		self::assertSame( '2026-09-16T10:30:00Z', $data['as_of'] );
		self::assertSame( 'Licensed Feed', $data['source']['label'] );
		self::assertArrayNotHasKey( 'provider_key', $data );
		self::assertArrayNotHasKey( 'provider_symbol', $data );
	}

	public function test_asset_without_enabled_quote_mapping_returns_explicit_not_found(): void {
		Functions\when( 'get_post_meta' )->justReturn( array() );

		$result = $this->service( new WpdbDouble() )->find_for_asset( 20 );

		self::assertTrue( is_wp_error( $result ) );
		self::assertSame( 'alborsa_platform_quote_not_found', $result->get_error_code() );
		self::assertSame( array( 'status' => 404 ), $result->get_error_data() );
	}

	private function service( WpdbDouble $wpdb ): QuoteReadService {
		return new QuoteReadService(
			new QuoteRepository( $wpdb ),
			new AssetQuoteMappingRepository(),
			new QuoteCache(),
			new ProviderHealthStore()
		);
	}

	/** @return array<string, mixed> */
	private function mapping(): array {
		return array(
			'provider_key'       => 'licensed-feed',
			'provider_symbol'    => '2222.SR',
			'data_capabilities'  => array( 'quotes' ),
			'data_delay_seconds' => 900,
			'attribution_label'  => 'Licensed Feed',
			'attribution_url'    => 'https://example.test/source',
			'enabled'            => true,
		);
	}
}

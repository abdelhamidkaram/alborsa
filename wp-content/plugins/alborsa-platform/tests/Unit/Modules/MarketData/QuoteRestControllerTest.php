<?php
/**
 * Quote REST controller tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\MarketData;

use Alborsa\Platform\Modules\Assets\AssetsModule;
use Alborsa\Platform\Modules\MarketData\AssetQuoteMappingRepository;
use Alborsa\Platform\Modules\MarketData\ProviderHealthStore;
use Alborsa\Platform\Modules\MarketData\QuoteCache;
use Alborsa\Platform\Modules\MarketData\QuoteReadService;
use Alborsa\Platform\Modules\MarketData\QuoteRepository;
use Alborsa\Platform\Modules\MarketData\QuoteRestController;
use Alborsa\Platform\Tests\Support\WpdbDouble;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;

final class QuoteRestControllerTest extends UnitTestCase {
	public function test_published_asset_quote_response_has_bounded_public_cache_policy(): void {
		Functions\when( 'get_post' )->justReturn(
			new \WP_Post(
				(object) array(
					'ID'          => 20,
					'post_type'   => AssetsModule::POST_TYPE,
					'post_name'   => '2222',
					'post_status' => 'publish',
				)
			)
		);
		Functions\when( 'get_post_meta' )->justReturn(
			array(
				array(
					'provider_key'       => 'licensed-feed',
					'provider_symbol'    => '2222.SR',
					'data_capabilities'  => array( 'quotes' ),
					'data_delay_seconds' => 900,
					'attribution_label'  => 'Licensed Feed',
					'attribution_url'    => '',
					'enabled'            => true,
				),
			)
		);
		Functions\when( 'wp_cache_get' )->justReturn(
			array(
				'price'            => '30.10',
				'currency'         => 'SAR',
				'market_timestamp' => '2026-09-16 10:30:00',
				'received_at'      => '2026-09-16 10:31:00',
				'delay_seconds'    => 900,
				'status'           => 'delayed',
			)
		);
		Functions\when( 'get_option' )->justReturn( array() );

		$response = $this->controller()->get_quote( new \WP_REST_Request( array( 'id' => 20 ) ) );

		self::assertInstanceOf( \WP_REST_Response::class, $response );
		self::assertSame( 200, $response->get_status() );
		self::assertSame( '30.10', $response->get_data()['price'] );
		self::assertSame( 'public, max-age=300, stale-while-revalidate=60', $response->get_headers()['Cache-Control'] );
	}

	private function controller(): QuoteRestController {
		$quotes = new QuoteReadService(
			new QuoteRepository( new WpdbDouble() ),
			new AssetQuoteMappingRepository(),
			new QuoteCache(),
			new ProviderHealthStore()
		);

		return new QuoteRestController( $quotes );
	}
}

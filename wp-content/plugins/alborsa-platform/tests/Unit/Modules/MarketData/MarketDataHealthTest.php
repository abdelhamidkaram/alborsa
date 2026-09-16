<?php
/**
 * Market data Site Health tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\MarketData;

use Alborsa\Platform\Modules\MarketData\MarketDataHealth;
use Alborsa\Platform\Modules\MarketData\MockQuoteProvider;
use Alborsa\Platform\Modules\MarketData\ProviderHealthStore;
use Alborsa\Platform\Modules\MarketData\QuoteProviderRegistry;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;

final class MarketDataHealthTest extends UnitTestCase {
	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'esc_html' )->returnArg();
	}

	public function test_missing_provider_is_reported_as_recommended_not_critical(): void {
		$health = new MarketDataHealth( new QuoteProviderRegistry( array() ), new ProviderHealthStore() );

		$report = $health->test();

		self::assertSame( 'recommended', $report['status'] );
		self::assertSame( 'alborsa_platform_market_data', $report['test'] );
	}

	public function test_provider_with_success_after_latest_failure_is_healthy(): void {
		Functions\when( 'get_option' )->justReturn(
			array(
				'mock' => array(
					'last_failure_at' => '2026-09-16 10:00:00',
					'last_success_at' => '2026-09-16 10:05:00',
				),
			)
		);
		$providers = new QuoteProviderRegistry( array( new MockQuoteProvider( array() ) ) );
		$health    = new MarketDataHealth( $providers, new ProviderHealthStore() );

		self::assertSame( 'good', $health->test()['status'] );
	}
}

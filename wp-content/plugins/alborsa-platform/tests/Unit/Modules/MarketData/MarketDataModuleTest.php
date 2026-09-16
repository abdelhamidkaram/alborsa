<?php
/**
 * Market data module lifecycle tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\MarketData;

use Alborsa\Platform\Modules\MarketData\MarketDataModule;
use Alborsa\Platform\Tests\UnitTestCase;

final class MarketDataModuleTest extends UnitTestCase {
	public function test_registration_defers_runtime_and_exposes_quote_route_hook(): void {
		( new MarketDataModule() )->register();

		self::assertSame( 10, has_action( 'init', MarketDataModule::class . '->initialize()' ) );
		self::assertSame( 10, has_action( 'rest_api_init', MarketDataModule::class . '->register_rest_routes()' ) );
	}
}

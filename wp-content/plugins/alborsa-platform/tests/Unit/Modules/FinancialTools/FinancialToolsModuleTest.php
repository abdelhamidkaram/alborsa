<?php
/**
 * Financial tools module lifecycle tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\FinancialTools;

use Alborsa\Platform\Modules\FinancialTools\FinancialToolsModule;
use Alborsa\Platform\Tests\UnitTestCase;

final class FinancialToolsModuleTest extends UnitTestCase {
	public function test_registration_defers_rest_routes_to_wordpress(): void {
		( new FinancialToolsModule() )->register();

		self::assertSame( 10, has_action( 'rest_api_init', FinancialToolsModule::class . '->register_rest_routes()' ) );
	}
}

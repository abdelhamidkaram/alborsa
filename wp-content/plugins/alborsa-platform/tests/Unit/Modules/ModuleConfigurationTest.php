<?php
/**
 * Product module configuration tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules;

use Alborsa\Platform\Foundation\FoundationModule;
use Alborsa\Platform\Modules\Assets\AssetsModule;
use Alborsa\Platform\Modules\Editorial\EditorialModule;
use Alborsa\Platform\Tests\UnitTestCase;

final class ModuleConfigurationTest extends UnitTestCase {
	public function test_configuration_wires_product_modules_after_foundation(): void {
		$modules = require ALBORSA_PLATFORM_PATH . 'config/modules.php';

		self::assertCount( 3, $modules );
		self::assertInstanceOf( FoundationModule::class, $modules[0] );
		self::assertInstanceOf( EditorialModule::class, $modules[1] );
		self::assertInstanceOf( AssetsModule::class, $modules[2] );
	}
}

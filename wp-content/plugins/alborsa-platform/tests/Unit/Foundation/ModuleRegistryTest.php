<?php
/**
 * Module registry tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Foundation;

use Alborsa\Platform\Foundation\Module;
use Alborsa\Platform\Foundation\ModuleRegistry;
use Alborsa\Platform\Tests\UnitTestCase;
use ArrayObject;

final class ModuleRegistryTest extends UnitTestCase {
	public function test_modules_register_once_in_configuration_order(): void {
		$registrations = new ArrayObject();
		$first_module  = $this->recording_module( $registrations, 'first' );
		$second_module = $this->recording_module( $registrations, 'second' );
		$registry      = new ModuleRegistry( array( $first_module, $second_module ) );

		$registry->register();
		$registry->register();

		self::assertSame( array( 'first', 'second' ), $registrations->getArrayCopy() );
	}

	private function recording_module( ArrayObject $registrations, string $name ): Module {
		return new class( $registrations, $name ) implements Module {
			private ArrayObject $registrations;
			private string $name;

			public function __construct( ArrayObject $registrations, string $name ) {
				$this->registrations = $registrations;
				$this->name          = $name;
			}

			public function register(): void {
				$this->registrations->append( $this->name );
			}
		};
	}
}

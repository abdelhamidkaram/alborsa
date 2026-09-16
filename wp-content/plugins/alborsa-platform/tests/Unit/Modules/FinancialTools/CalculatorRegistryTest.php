<?php
/**
 * Financial calculator registry tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\FinancialTools;

use Alborsa\Platform\Modules\FinancialTools\CalculationInput;
use Alborsa\Platform\Modules\FinancialTools\CalculatorRegistry;
use Alborsa\Platform\Modules\FinancialTools\ProfitLossCalculator;
use Alborsa\Platform\Tests\UnitTestCase;

final class CalculatorRegistryTest extends UnitTestCase {
	public function test_catalog_marks_decision_bound_tools_as_unavailable(): void {
		$catalog = $this->registry()->catalog();

		self::assertSame( 'available', $catalog[0]['status'] );
		self::assertSame( 'provider_pending', $catalog[1]['status'] );
		self::assertSame( 'D-008', $catalog[1]['decision'] );
		self::assertSame( 'methodology_pending', $catalog[2]['status'] );
		self::assertSame( 'D-006', $catalog[2]['decision'] );
	}

	public function test_blocked_and_unknown_tools_return_distinct_errors(): void {
		$blocked = $this->registry()->calculate( 'zakat', array() );
		$unknown = $this->registry()->calculate( 'unknown', array() );

		self::assertTrue( is_wp_error( $blocked ) );
		self::assertSame( 409, $blocked->get_error_data()['status'] );
		self::assertSame( 'D-006', $blocked->get_error_data()['decision'] );
		self::assertTrue( is_wp_error( $unknown ) );
		self::assertSame( 404, $unknown->get_error_data()['status'] );
	}

	private function registry(): CalculatorRegistry {
		return new CalculatorRegistry( array( new ProfitLossCalculator( new CalculationInput() ) ) );
	}
}

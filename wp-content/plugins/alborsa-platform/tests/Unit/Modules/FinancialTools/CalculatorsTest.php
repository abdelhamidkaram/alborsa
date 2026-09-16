<?php
/**
 * Deterministic financial calculator tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\FinancialTools;

use Alborsa\Platform\Modules\FinancialTools\CalculationInput;
use Alborsa\Platform\Modules\FinancialTools\CompoundGrowthCalculator;
use Alborsa\Platform\Modules\FinancialTools\PipValueCalculator;
use Alborsa\Platform\Modules\FinancialTools\PositionSizeCalculator;
use Alborsa\Platform\Modules\FinancialTools\ProfitLossCalculator;
use Alborsa\Platform\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class CalculatorsTest extends UnitTestCase {
	/** @return iterable<string, array{string, string}> */
	public static function position_sides(): iterable {
		yield 'long' => array( 'long', '48' );
		yield 'short' => array( 'short', '-52' );
	}

	#[DataProvider( 'position_sides' )]
	public function test_profit_loss_supports_long_and_short_positions( string $side, string $expected ): void {
		$result = ( new ProfitLossCalculator( new CalculationInput() ) )->calculate(
			array(
				'entry_price' => '10',
				'exit_price'  => '15',
				'quantity'    => '10',
				'fees'        => '2',
				'side'        => $side,
			)
		);

		self::assertIsArray( $result );
		self::assertSame( $expected, $result['net_profit_loss'] );
	}

	public function test_position_size_uses_explicit_risk_and_pip_value(): void {
		$result = ( new PositionSizeCalculator( new CalculationInput() ) )->calculate(
			array(
				'account_balance'    => '10000',
				'risk_percent'       => '1',
				'stop_loss_pips'     => '50',
				'pip_value_per_unit' => '0.1',
			)
		);

		self::assertIsArray( $result );
		self::assertSame( '100', $result['risk_amount'] );
		self::assertSame( '20', $result['position_units'] );
	}

	public function test_pip_value_never_infers_the_conversion_rate(): void {
		$result = ( new PipValueCalculator( new CalculationInput() ) )->calculate(
			array(
				'position_units'        => '100000',
				'pip_size'              => '0.0001',
				'quote_to_account_rate' => '1',
			)
		);

		self::assertSame( array( 'pip_value' => '10' ), $result );
	}

	public function test_compound_growth_uses_monthly_end_of_period_contributions(): void {
		$result = ( new CompoundGrowthCalculator( new CalculationInput() ) )->calculate(
			array(
				'initial_amount'       => '1000',
				'monthly_contribution' => '100',
				'annual_rate_percent'  => '12',
				'years'                => '1',
			)
		);

		self::assertIsArray( $result );
		self::assertSame( '2200', $result['total_contributions'] );
		self::assertSame( '195.08', $result['estimated_growth'] );
		self::assertSame( '2395.08', $result['future_value'] );
	}
}

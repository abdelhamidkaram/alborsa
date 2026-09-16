<?php
/**
 * Financial calculator input boundary tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\FinancialTools;

use Alborsa\Platform\Modules\FinancialTools\CalculationInput;
use Alborsa\Platform\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class CalculationInputTest extends UnitTestCase {
	/** @return iterable<string, array{mixed}> */
	public static function invalid_decimals(): iterable {
		yield 'native float' => array( 1.2 );
		yield 'scientific notation' => array( '1e4' );
		yield 'separator' => array( '1,000' );
		yield 'too many decimals' => array( '1.123456789' );
		yield 'outside range' => array( '101' );
	}

	#[DataProvider( 'invalid_decimals' )]
	public function test_decimal_rejects_ambiguous_or_out_of_range_values( mixed $value ): void {
		$result = ( new CalculationInput() )->decimal( array( 'amount' => $value ), 'amount', 0.0, 100.0 );

		self::assertTrue( is_wp_error( $result ) );
		self::assertSame( 'amount', $result->get_error_data()['field'] );
	}

	public function test_decimal_accepts_bounded_decimal_strings_and_formats_results(): void {
		$input = new CalculationInput();

		self::assertSame( 12.5, $input->decimal( array( 'amount' => '12.50000000' ), 'amount', 0.0, 100.0 ) );
		self::assertSame( '12.5', $input->format( 12.5 ) );
	}
}

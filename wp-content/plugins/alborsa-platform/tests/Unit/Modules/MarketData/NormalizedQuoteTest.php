<?php
/**
 * Normalized quote tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\MarketData;

use Alborsa\Platform\Modules\MarketData\NormalizedQuote;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;

final class NormalizedQuoteTest extends UnitTestCase {
	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'wp_json_encode' )->alias(
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Implements the isolated wp_json_encode boundary.
			static fn( mixed $value, int $flags = 0 ): string|false => json_encode( $value, $flags )
		);
	}

	public function test_valid_quote_normalizes_decimals_and_timestamps(): void {
		$quote = NormalizedQuote::from_array(
			$this->fixture(
				array(
					'price'            => '00123.4500',
					'absolute_change'  => '-01.2000',
					'market_timestamp' => '2026-09-16T13:30:00+03:00',
					'received_at'      => '2026-09-16T10:31:00Z',
					'delay_seconds'    => '900',
					'status'           => 'delayed',
				)
			)
		);

		self::assertInstanceOf( NormalizedQuote::class, $quote );
		$values = $quote->to_array();
		self::assertSame( '123.45', $values['price'] );
		self::assertSame( '-1.2', $values['absolute_change'] );
		self::assertSame( '2026-09-16 10:30:00', $values['market_timestamp'] );
		self::assertSame( 900, $values['delay_seconds'] );
		self::assertMatchesRegularExpression( '/\A[a-f0-9]{64}\z/', $quote->payload_hash() );
	}

	public function test_unavailable_quote_allows_missing_price_and_currency(): void {
		$quote = NormalizedQuote::from_array(
			$this->fixture(
				array(
					'price'    => null,
					'currency' => null,
					'status'   => 'unavailable',
				)
			)
		);

		self::assertInstanceOf( NormalizedQuote::class, $quote );
		self::assertNull( $quote->to_array()['price'] );
	}

	public function test_relative_timestamp_is_rejected(): void {
		$quote = NormalizedQuote::from_array( $this->fixture( array( 'market_timestamp' => 'tomorrow' ) ) );

		self::assertTrue( is_wp_error( $quote ) );
		self::assertSame( 'alborsa_platform_quote_invalid_market_timestamp', $quote->get_error_code() );
	}

	public function test_available_quote_requires_price_and_currency(): void {
		$quote = NormalizedQuote::from_array(
			$this->fixture(
				array(
					'price'    => null,
					'currency' => '',
				)
			)
		);

		self::assertTrue( is_wp_error( $quote ) );
		self::assertSame( 'alborsa_platform_quote_missing_market_value', $quote->get_error_code() );
	}

	/**
	 * @dataProvider invalidNumberProvider
	 */
	public function test_invalid_number_is_rejected( string $field, mixed $value, string $error_code ): void {
		$quote = NormalizedQuote::from_array( $this->fixture( array( $field => $value ) ) );

		self::assertTrue( is_wp_error( $quote ) );
		self::assertSame( $error_code, $quote->get_error_code() );
	}

	/** @return iterable<string, array{string, mixed, string}> */
	public static function invalidNumberProvider(): iterable {
		yield 'negative price' => array( 'price', '-1.00', 'alborsa_platform_quote_invalid_price' );
		yield 'scientific percent change' => array( 'percent_change', '1e3', 'alborsa_platform_quote_invalid_percent_change' );
		yield 'floating-point input' => array( 'price', 1.25, 'alborsa_platform_quote_invalid_price' );
		yield 'negative delay' => array( 'delay_seconds', -1, 'alborsa_platform_quote_invalid_delay' );
	}

	/**
	 * @param array<string, mixed> $overrides Fixture overrides.
	 * @return array<string, mixed>
	 */
	private function fixture( array $overrides = array() ): array {
		return array_merge(
			array(
				'provider_key'     => 'mock',
				'provider_symbol'  => '2222',
				'price'            => '30.10',
				'open_price'       => '29.50',
				'previous_close'   => '29.70',
				'high_price'       => '30.50',
				'low_price'        => '29.40',
				'volume'           => '1000000',
				'absolute_change'  => '0.40',
				'percent_change'   => '1.3468',
				'currency'         => 'SAR',
				'market_timestamp' => '2026-09-16T10:30:00Z',
				'received_at'      => '2026-09-16T10:31:00Z',
				'delay_seconds'    => 900,
				'status'           => 'delayed',
			),
			$overrides
		);
	}
}

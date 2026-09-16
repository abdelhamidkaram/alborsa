<?php
/**
 * Mock quote provider tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\MarketData;

use Alborsa\Platform\Modules\MarketData\MockQuoteProvider;
use Alborsa\Platform\Modules\MarketData\NormalizedQuote;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;

final class MockQuoteProviderTest extends UnitTestCase {
	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'wp_json_encode' )->alias(
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Implements the isolated wp_json_encode boundary.
			static fn( mixed $value, int $flags = 0 ): string|false => json_encode( $value, $flags )
		);
	}

	public function test_provider_returns_unique_requested_fixtures_in_order(): void {
		$provider = new MockQuoteProvider(
			array(
				'2222'   => $this->quote( '2222' ),
				'EURUSD' => $this->quote( 'EURUSD' ),
			)
		);

		$result = $provider->fetch_quotes( array( 'EURUSD', '2222', 'EURUSD' ) );

		self::assertFalse( is_wp_error( $result ) );
		self::assertSame(
			array( 'EURUSD', '2222' ),
			array_map( static fn( NormalizedQuote $quote ): string => $quote->provider_symbol(), $result )
		);
		self::assertSame( 'mock', $provider->key() );
	}

	public function test_provider_rejects_missing_fixture(): void {
		$provider = new MockQuoteProvider( array( '2222' => $this->quote( '2222' ) ) );

		$result = $provider->fetch_quotes( array( 'MISSING' ) );

		self::assertTrue( is_wp_error( $result ) );
		self::assertSame( 'alborsa_platform_mock_quote_fixture_missing', $result->get_error_code() );
	}

	public function test_provider_rejects_empty_batch(): void {
		$result = ( new MockQuoteProvider( array() ) )->fetch_quotes( array() );

		self::assertTrue( is_wp_error( $result ) );
		self::assertSame( 'alborsa_platform_mock_quote_invalid_batch', $result->get_error_code() );
	}

	private function quote( string $provider_symbol ): NormalizedQuote {
		$quote = NormalizedQuote::from_array(
			array(
				'provider_key'     => 'mock',
				'provider_symbol'  => $provider_symbol,
				'price'            => '1.25',
				'currency'         => 'USD',
				'market_timestamp' => '2026-09-16T10:30:00Z',
				'received_at'      => '2026-09-16T10:31:00Z',
				'delay_seconds'    => 900,
				'status'           => 'delayed',
			)
		);

		if ( is_wp_error( $quote ) ) {
			self::fail( $quote->get_error_message() );
		}

		return $quote;
	}
}

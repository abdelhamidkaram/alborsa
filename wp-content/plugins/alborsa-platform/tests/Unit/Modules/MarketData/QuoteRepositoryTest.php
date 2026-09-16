<?php
/**
 * Latest quote repository tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\MarketData;

use Alborsa\Platform\Modules\MarketData\NormalizedQuote;
use Alborsa\Platform\Modules\MarketData\QuoteRepository;
use Alborsa\Platform\Tests\Support\WpdbDouble;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;

final class QuoteRepositoryTest extends UnitTestCase {
	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'wp_json_encode' )->alias(
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Implements the isolated wp_json_encode boundary.
			static fn( mixed $value, int $flags = 0 ): string|false => json_encode( $value, $flags )
		);
	}

	public function test_save_upserts_exact_decimal_snapshot_without_float_conversion(): void {
		$wpdb       = new WpdbDouble();
		$repository = new QuoteRepository( $wpdb );
		$quote      = $this->quote( array( 'price' => '30.1234567890' ) );

		$result = $repository->save( 20, $quote );

		self::assertTrue( $result );
		self::assertStringContainsString( 'wp_alb_market_quotes', (string) $wpdb->last_query );
		self::assertStringContainsString( "'30.123456789'", (string) $wpdb->last_query );
		self::assertStringContainsString( 'open_price, previous_close', (string) $wpdb->last_query );
		self::assertStringContainsString( 'VALUES (20,', (string) $wpdb->last_query );
		self::assertStringContainsString( 'ON DUPLICATE KEY UPDATE', (string) $wpdb->last_query );
	}

	public function test_save_rejects_invalid_asset_before_query(): void {
		$wpdb   = new WpdbDouble();
		$result = ( new QuoteRepository( $wpdb ) )->save( 0, $this->quote() );

		self::assertTrue( is_wp_error( $result ) );
		self::assertSame( 'alborsa_platform_quote_repository_invalid_asset', $result->get_error_code() );
		self::assertNull( $wpdb->last_query );
	}

	public function test_save_returns_actionable_database_error(): void {
		$wpdb = new class() extends WpdbDouble {
			public function query( string $query ): int|false {
				parent::query( $query );
				$this->last_error = 'Simulated quote write failure';

				return false;
			}
		};

		$result = ( new QuoteRepository( $wpdb ) )->save( 20, $this->quote() );

		self::assertTrue( is_wp_error( $result ) );
		self::assertSame( 'alborsa_platform_quote_repository_save_failed', $result->get_error_code() );
		self::assertStringContainsString( 'Simulated quote write failure', $result->get_error_message() );
	}

	public function test_find_reads_one_exact_asset_provider_snapshot(): void {
		$wpdb = new class() extends WpdbDouble {
			public function get_results( string $query, string|int $output = 'OBJECT' ): ?array {
				parent::get_results( $query, $output );

				return array(
					array(
						'asset_id'     => '20',
						'provider_key' => 'mock',
						'price'        => '30.1000000000',
					),
				);
			}
		};

		$result = ( new QuoteRepository( $wpdb ) )->find( 20, 'mock' );

		self::assertSame( '30.1000000000', $result['price'] );
		self::assertStringContainsString( 'asset_id = 20', (string) $wpdb->last_query );
		self::assertStringContainsString( "provider_key = 'mock'", (string) $wpdb->last_query );
		self::assertStringContainsString( 'LIMIT 1', (string) $wpdb->last_query );
	}

	public function test_find_returns_null_when_snapshot_does_not_exist(): void {
		$result = ( new QuoteRepository( new WpdbDouble() ) )->find( 20, 'mock' );

		self::assertNull( $result );
	}

	public function test_find_returns_actionable_database_error(): void {
		$wpdb = new class() extends WpdbDouble {
			public function get_results( string $query, string|int $output = 'OBJECT' ): ?array {
				parent::get_results( $query, $output );
				$this->last_error = 'Simulated quote read failure';

				return null;
			}
		};

		$result = ( new QuoteRepository( $wpdb ) )->find( 20, 'mock' );

		self::assertTrue( is_wp_error( $result ) );
		self::assertSame( 'alborsa_platform_quote_repository_read_failed', $result->get_error_code() );
		self::assertStringContainsString( 'Simulated quote read failure', $result->get_error_message() );
	}

	/**
	 * @param array<string, mixed> $overrides Quote overrides.
	 */
	private function quote( array $overrides = array() ): NormalizedQuote {
		$quote = NormalizedQuote::from_array(
			array_merge(
				array(
					'provider_key'     => 'mock',
					'provider_symbol'  => '2222',
					'price'            => '30.10',
					'currency'         => 'SAR',
					'market_timestamp' => '2026-09-16T10:30:00Z',
					'received_at'      => '2026-09-16T10:31:00Z',
					'delay_seconds'    => 900,
					'status'           => 'delayed',
				),
				$overrides
			)
		);

		if ( is_wp_error( $quote ) ) {
			self::fail( $quote->get_error_message() );
		}

		return $quote;
	}
}

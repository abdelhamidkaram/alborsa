<?php
/**
 * Quote synchronization service tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\MarketData;

use Alborsa\Platform\Modules\MarketData\MockQuoteProvider;
use Alborsa\Platform\Modules\MarketData\NormalizedQuote;
use Alborsa\Platform\Modules\MarketData\ProviderHealthStore;
use Alborsa\Platform\Modules\MarketData\QuoteCache;
use Alborsa\Platform\Modules\MarketData\QuoteMapping;
use Alborsa\Platform\Modules\MarketData\QuoteRepository;
use Alborsa\Platform\Modules\MarketData\QuoteSyncService;
use Alborsa\Platform\Tests\Support\WpdbDouble;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;

final class QuoteSyncServiceTest extends UnitTestCase {
	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'wp_json_encode' )->alias(
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Implements the isolated wp_json_encode boundary.
			static fn( mixed $value, int $flags = 0 ): string|false => json_encode( $value, $flags )
		);
		Functions\when( 'wp_cache_set' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( array() );
		Functions\when( 'sanitize_key' )->returnArg();
		Functions\when( 'current_time' )->justReturn( '2026-09-16 11:00:00' );
		Functions\when( 'update_option' )->justReturn( true );
	}

	public function test_shared_provider_symbol_is_fetched_once_and_saved_for_each_translation(): void {
		$wpdb     = new class() extends WpdbDouble {
			public int $writes = 0;

			public function query( string $query ): int|false {
				++$this->writes;

				return parent::query( $query );
			}
		};
		$quote    = $this->quote();
		$provider = new MockQuoteProvider( array( '2222.SR' => $quote ) );
		$mapping  = $this->mapping();
		$report   = $this->service( $wpdb )->sync(
			$provider,
			array(
				array(
					'asset_id' => 20,
					'mapping'  => $mapping,
				),
				array(
					'asset_id' => 21,
					'mapping'  => $mapping,
				),
			)
		);

		self::assertFalse( is_wp_error( $report ) );
		self::assertSame( 2, $report['assets_saved'] );
		self::assertSame( 1, $report['symbols_requested'] );
		self::assertSame( 2, $wpdb->writes );
	}

	public function test_provider_failure_preserves_existing_snapshots_and_records_failure(): void {
		$wpdb      = new WpdbDouble();
		$health    = array();
		$provider  = new MockQuoteProvider( array( '2222.SR' => $this->quote() ) );
		$mapping_a = $this->mapping();
		$mapping_b = $this->mapping( '1120.SR' );
		Functions\when( 'update_option' )->alias(
			static function ( string $key, mixed $value ) use ( &$health ): bool {
				if ( ProviderHealthStore::OPTION === $key && is_array( $value ) ) {
					$health = $value;
				}

				return true;
			}
		);

		$result = $this->service( $wpdb )->sync(
			$provider,
			array(
				array(
					'asset_id' => 20,
					'mapping'  => $mapping_a,
				),
				array(
					'asset_id' => 30,
					'mapping'  => $mapping_b,
				),
			)
		);

		self::assertTrue( is_wp_error( $result ) );
		self::assertSame( 'alborsa_platform_mock_quote_fixture_missing', $result->get_error_code() );
		self::assertNull( $wpdb->last_query );
		self::assertSame( 'alborsa_platform_mock_quote_fixture_missing', $health['mock']['last_error_code'] );
	}

	private function service( WpdbDouble $wpdb ): QuoteSyncService {
		return new QuoteSyncService(
			new QuoteRepository( $wpdb ),
			new QuoteCache(),
			new ProviderHealthStore()
		);
	}

	private function mapping( string $symbol = '2222.SR' ): QuoteMapping {
		$mapping = QuoteMapping::from_array(
			array(
				'provider_key'       => 'mock',
				'provider_symbol'    => $symbol,
				'data_capabilities'  => array( 'quotes' ),
				'data_delay_seconds' => 900,
				'attribution_label'  => 'Test feed',
				'attribution_url'    => '',
				'enabled'            => true,
			)
		);

		if ( null === $mapping ) {
			self::fail( 'Expected a valid quote mapping.' );
		}

		return $mapping;
	}

	private function quote(): NormalizedQuote {
		$quote = NormalizedQuote::from_array(
			array(
				'provider_key'     => 'mock',
				'provider_symbol'  => '2222.SR',
				'price'            => '30.10',
				'currency'         => 'SAR',
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

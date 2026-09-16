<?php
/**
 * Asset metadata tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\Assets;

use Alborsa\Platform\Modules\Assets\AssetMeta;
use Alborsa\Platform\Modules\Assets\AssetsModule;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\DataProvider;

final class AssetMetaTest extends UnitTestCase {
	public function test_registration_exposes_public_fields_but_keeps_provider_mappings_internal(): void {
		$registrations = array();

		Functions\when( 'register_post_meta' )->alias(
			static function ( string $post_type, string $meta_key, array $arguments ) use ( &$registrations ): bool {
				$registrations[ $meta_key ] = array(
					'post_type' => $post_type,
					'arguments' => $arguments,
				);

				return true;
			}
		);

		( new AssetMeta() )->register();

		$public_fields = array(
			AssetMeta::CANONICAL_SYMBOL,
			AssetMeta::BASE_CURRENCY,
			AssetMeta::QUOTE_CURRENCY,
			AssetMeta::ISIN,
			AssetMeta::OFFICIAL_WEBSITE,
			AssetMeta::LISTING_STATUS,
		);

		self::assertSame(
			array_merge( $public_fields, array( AssetMeta::PROVIDER_MAPPINGS ) ),
			array_keys( $registrations )
		);

		foreach ( $public_fields as $meta_key ) {
			$this->assert_registration( $registrations[ $meta_key ], 'string', true );
		}

		$this->assert_registration( $registrations[ AssetMeta::PROVIDER_MAPPINGS ], 'array', false );
	}

	#[DataProvider( 'identifier_cases' )]
	public function test_identifier_normalizers_reject_invalid_values(
		string $normalizer,
		mixed $input,
		string $expected
	): void {
		$this->stub_text_sanitizers();

		$normalized = ( new AssetMeta() )->{$normalizer}( $input );

		self::assertSame( $expected, $normalized );
	}

	/**
	 * @return iterable<string, array{string, mixed, string}>
	 */
	public static function identifier_cases(): iterable {
		yield 'symbol normalization' => array( 'sanitize_symbol', ' sar/usd ', 'SAR/USD' );
		yield 'symbol invalid characters' => array( 'sanitize_symbol', 'SAR USD', '' );
		yield 'symbol non-scalar' => array( 'sanitize_symbol', array( 'SAR' ), '' );
		yield 'currency normalization' => array( 'sanitize_currency', ' sar ', 'SAR' );
		yield 'currency invalid length' => array( 'sanitize_currency', 'USDT', '' );
		yield 'ISIN normalization' => array( 'sanitize_isin', ' sa0007879113 ', 'SA0007879113' );
		yield 'ISIN invalid format' => array( 'sanitize_isin', 'SA123', '' );
		yield 'listing status normalization' => array( 'sanitize_listing_status', ' Temporarily Suspended ', 'temporarilysuspended' );
		yield 'listing status length limit' => array( 'sanitize_listing_status', str_repeat( 'a', 33 ), '' );
	}

	public function test_provider_mappings_are_normalized_and_invalid_records_are_dropped(): void {
		$this->stub_text_sanitizers();
		$this->stub_url_sanitizer();
		Functions\when( 'rest_sanitize_boolean' )->alias(
			static fn( mixed $value ): bool => filter_var( $value, FILTER_VALIDATE_BOOLEAN )
		);

		$mappings = ( new AssetMeta() )->sanitize_provider_mappings(
			array(
				array(
					'provider_key'       => 'Twelve Data',
					'provider_symbol'    => ' 2222.SR ',
					'data_capabilities'  => array( 'Quotes', 'reference data', 'Quotes', array() ),
					'data_delay_seconds' => -30,
					'attribution_label'  => ' Twelve Data ',
					'attribution_url'    => 'https://example.test/source',
					'enabled'            => 'true',
				),
				array( 'provider_key' => 'missing-symbol' ),
				'not-a-record',
			)
		);

		self::assertSame(
			array(
				array(
					'provider_key'       => 'twelvedata',
					'provider_symbol'    => '2222.SR',
					'data_capabilities'  => array( 'quotes', 'referencedata' ),
					'data_delay_seconds' => 0,
					'attribution_label'  => 'Twelve Data',
					'attribution_url'    => 'https://example.test/source',
					'enabled'            => true,
				),
			),
			$mappings
		);
	}

	public function test_meta_write_requires_permission_for_the_asset(): void {
		$checked_post_id = 0;

		Functions\when( 'current_user_can' )->alias(
			static function ( string $capability, int $post_id ) use ( &$checked_post_id ): bool {
				if ( 'edit_post' === $capability ) {
					$checked_post_id = $post_id;
				}

				return true;
			}
		);

		$allowed = ( new AssetMeta() )->can_edit( false, AssetMeta::CANONICAL_SYMBOL, 42 );

		self::assertTrue( $allowed );
		self::assertSame( 42, $checked_post_id );
	}

	/**
	 * @param array{post_type: string, arguments: array<string, mixed>} $registration Meta registration.
	 */
	private function assert_registration( array $registration, string $type, bool $show_in_rest ): void {
		self::assertSame( AssetsModule::POST_TYPE, $registration['post_type'] );
		self::assertSame( $type, $registration['arguments']['type'] );
		self::assertTrue( $registration['arguments']['single'] );
		self::assertSame( $show_in_rest, $registration['arguments']['show_in_rest'] );
		self::assertIsCallable( $registration['arguments']['sanitize_callback'] );
		self::assertIsCallable( $registration['arguments']['auth_callback'] );
	}

	private function stub_text_sanitizers(): void {
		Functions\when( 'sanitize_text_field' )->alias(
			static fn( string $text ): string => trim( $text )
		);
		Functions\when( 'sanitize_key' )->alias(
			static fn( string $key ): string => preg_replace( '/[^a-z0-9_-]/', '', strtolower( $key ) ) ?? ''
		);
	}

	private function stub_url_sanitizer(): void {
		Functions\when( 'esc_url_raw' )->alias(
			static function ( string $url, array $protocols ): string {
				$scheme = str_starts_with( $url, 'https://' ) ? 'https' : 'http';

				return str_starts_with( $url, $scheme . '://' ) && in_array( $scheme, $protocols, true ) ? $url : '';
			}
		);
	}
}

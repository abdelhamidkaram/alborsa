<?php
/**
 * Broker metadata tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\Brokers;

use Alborsa\Platform\Modules\Brokers\BrokerMeta;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;

final class BrokerMetaTest extends UnitTestCase {
	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'sanitize_text_field' )->alias( static fn( string $text ): string => trim( $text ) );
		Functions\when( 'sanitize_textarea_field' )->alias( static fn( string $text ): string => trim( $text ) );
		Functions\when( 'sanitize_key' )->alias(
			static fn( string $key ): string => preg_replace( '/[^a-z0-9_-]/', '', strtolower( $key ) ) ?? ''
		);
		Functions\when( 'esc_url_raw' )->alias(
			static fn( string $url ): string => str_starts_with( $url, 'https://' ) ? $url : ''
		);
		Functions\when( 'rest_sanitize_boolean' )->alias(
			static fn( bool|int|string $value ): bool => filter_var( $value, FILTER_VALIDATE_BOOLEAN )
		);
	}

	public function test_licenses_require_identity_and_normalize_verification_evidence(): void {
		$licenses = ( new BrokerMeta() )->sanitize_licenses(
			array(
				array(
					'regulator'           => '  FCA ',
					'country'             => ' UK ',
					'legal_entity'        => ' Example Markets Ltd ',
					'license_number'      => ' 12345 ',
					'verification_status' => 'VERIFIED',
					'source_url'          => 'https://example.test/register/12345',
					'verified_at'         => '2026-09-16',
					'internal_notes'      => ' Internal only ',
				),
				array( 'country' => 'UK' ),
			)
		);

		self::assertCount( 1, $licenses );
		self::assertSame( 'FCA', $licenses[0]['regulator'] );
		self::assertSame( BrokerMeta::LICENSE_VERIFIED, $licenses[0]['verification_status'] );
		self::assertSame( '2026-09-16', $licenses[0]['verified_at'] );
		self::assertSame( 'Internal only', $licenses[0]['internal_notes'] );
	}

	public function test_registration_keeps_structured_licenses_out_of_core_rest(): void {
		$registrations = array();
		Functions\when( 'register_post_meta' )->alias(
			static function ( string $post_type, string $meta_key, array $arguments ) use ( &$registrations ): bool {
				unset( $post_type );
				$registrations[ $meta_key ] = $arguments;

				return true;
			}
		);

		( new BrokerMeta() )->register();

		self::assertFalse( $registrations[ BrokerMeta::LICENSES ]['show_in_rest'] );
		self::assertTrue( $registrations[ BrokerMeta::LEGAL_NAME ]['show_in_rest'] );
		self::assertSame( 'array', $registrations[ BrokerMeta::PLATFORMS ]['type'] );
	}
}

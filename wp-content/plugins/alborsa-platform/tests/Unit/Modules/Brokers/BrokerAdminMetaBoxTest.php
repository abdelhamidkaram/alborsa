<?php
/**
 * Broker admin meta box tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\Brokers;

use Alborsa\Platform\Modules\Brokers\BrokerAdminMetaBox;
use Alborsa\Platform\Modules\Brokers\BrokerMeta;
use Alborsa\Platform\Modules\Brokers\BrokersModule;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;

final class BrokerAdminMetaBoxTest extends UnitTestCase {
	/** @var array<string, mixed> */
	private array $original_post;

	protected function setUp(): void {
		parent::setUp();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Preserves isolated test state before exercising nonce verification.
		$this->original_post = $_POST;
		Functions\when( 'sanitize_text_field' )->alias( static fn( string $text ): string => trim( $text ) );
		Functions\when( 'sanitize_textarea_field' )->alias( static fn( string $text ): string => trim( $text ) );
		Functions\when( 'sanitize_key' )->alias(
			static fn( string $key ): string => preg_replace( '/[^a-z0-9_-]/', '', strtolower( $key ) ) ?? ''
		);
		Functions\when( 'esc_url_raw' )->alias(
			static fn( string $url ): string => str_starts_with( $url, 'https://' ) ? $url : ''
		);
		Functions\when( 'wp_unslash' )->returnArg();
		Functions\when( 'get_option' )->justReturn( array() );
		Functions\when( 'update_option' )->justReturn( true );
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'current_time' )->justReturn( '2026-09-16 12:00:00' );
	}

	protected function tearDown(): void {
		$_POST = $this->original_post;
		parent::tearDown();
	}

	public function test_valid_admin_save_sanitizes_profile_and_license_fields(): void {
		$stored = array();
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'wp_verify_nonce' )->justReturn( 1 );
		Functions\when( 'update_post_meta' )->alias(
			static function ( int $post_id, string $meta_key, mixed $value ) use ( &$stored ): int {
				unset( $post_id );
				$stored[ $meta_key ] = $value;

				return 1;
			}
		);
		Functions\when( 'delete_post_meta' )->justReturn( true );
		$_POST = array(
			'alborsa_platform_broker_nonce' => 'valid',
			'alborsa_broker'                => array(
				BrokerMeta::LEGAL_NAME       => ' Example Markets Ltd ',
				BrokerMeta::OFFICIAL_WEBSITE => 'https://example.test',
				BrokerMeta::MINIMUM_DEPOSIT  => '100.50',
				BrokerMeta::DEPOSIT_CURRENCY => 'sar',
				BrokerMeta::SERVICES         => 'Forex, Stocks, Forex',
				BrokerMeta::ARABIC_SUPPORT   => '1',
				'licenses'                   => array(
					array(
						'regulator'           => 'CMA',
						'license_number'      => '123',
						'verification_status' => 'verified',
						'source_url'          => 'https://example.test/register/123',
						'verified_at'         => '2026-09-16',
					),
				),
			),
		);

		( new BrokerAdminMetaBox( new BrokerMeta() ) )->save( 50, $this->broker() );

		self::assertSame( 'Example Markets Ltd', $stored[ BrokerMeta::LEGAL_NAME ] );
		self::assertSame( 'SAR', $stored[ BrokerMeta::DEPOSIT_CURRENCY ] );
		self::assertSame( array( 'Forex', 'Stocks' ), $stored[ BrokerMeta::SERVICES ] );
		self::assertSame( 1, $stored[ BrokerMeta::ARABIC_SUPPORT ] );
		self::assertSame( 'CMA', $stored[ BrokerMeta::LICENSES ][0]['regulator'] );
	}

	private function broker(): \WP_Post {
		return new \WP_Post(
			(object) array(
				'ID'          => 50,
				'post_type'   => BrokersModule::POST_TYPE,
				'post_name'   => 'example',
				'post_status' => 'draft',
			)
		);
	}
}

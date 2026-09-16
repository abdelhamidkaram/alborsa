<?php
/**
 * Editorial metadata editor tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\Editorial;

use Alborsa\Platform\Modules\Editorial\EditorialAdminMetaBox;
use Alborsa\Platform\Modules\Editorial\EditorialMeta;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;

final class EditorialAdminMetaBoxTest extends UnitTestCase {
	/** @var array<string, mixed> */
	private array $original_post;

	protected function setUp(): void {
		parent::setUp();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Preserves isolated test state before exercising verification.
		$this->original_post = $_POST;
		Functions\when( 'sanitize_text_field' )->alias( static fn( string $value ): string => trim( $value ) );
		Functions\when( 'sanitize_textarea_field' )->alias( static fn( string $value ): string => trim( $value ) );
		Functions\when( 'sanitize_key' )->alias( static fn( string $value ): string => strtolower( $value ) );
		Functions\when( 'esc_url_raw' )->returnArg();
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

	public function test_valid_save_sanitizes_and_stores_editorial_provenance(): void {
		$stored = array();
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'wp_verify_nonce' )->justReturn( 1 );
		Functions\when( 'get_userdata' )->alias(
			static fn( int $user_id ): \WP_User => new \WP_User(
				(object) array(
					'ID'           => $user_id,
					'display_name' => 'Reviewer',
				)
			)
		);
		Functions\when( 'user_can' )->justReturn( true );
		Functions\when( 'update_post_meta' )->alias(
			static function ( int $post_id, string $meta_key, mixed $value ) use ( &$stored ): int {
				unset( $post_id );
				$stored[ $meta_key ] = $value;

				return 1;
			}
		);
		Functions\when( 'delete_post_meta' )->justReturn( true );
		$_POST = array(
			'alborsa_platform_editorial_nonce' => 'valid',
			'alborsa_editorial'                => array(
				EditorialMeta::SOURCE_NAME      => ' Source ',
				EditorialMeta::SOURCE_URL       => 'https://example.test/story',
				EditorialMeta::REVIEWER_ID      => '7',
				EditorialMeta::LAST_REVIEWED_AT => '2026-09-16',
				EditorialMeta::DISCLOSURE       => ' Editorial disclosure ',
			),
		);

		$post = new \WP_Post(
			(object) array(
				'ID'        => 50,
				'post_type' => 'post',
				'post_name' => 'story',
			)
		);
		( new EditorialAdminMetaBox( new EditorialMeta() ) )->save( 50, $post );

		self::assertSame( 'Source', $stored[ EditorialMeta::SOURCE_NAME ] );
		self::assertSame( 7, $stored[ EditorialMeta::REVIEWER_ID ] );
		self::assertSame( '2026-09-16', $stored[ EditorialMeta::LAST_REVIEWED_AT ] );
		self::assertSame( 'Editorial disclosure', $stored[ EditorialMeta::DISCLOSURE ] );
	}
}

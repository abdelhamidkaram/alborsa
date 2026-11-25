<?php
/**
 * Static page editor control tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\Editorial;

use Alborsa\Platform\Modules\Editorial\StaticPageAdminMetaBox;
use Alborsa\Platform\Modules\Editorial\StaticPageMeta;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;

final class StaticPageAdminMetaBoxTest extends UnitTestCase {
	/** @var array<string, mixed> */
	private array $original_post;

	protected function setUp(): void {
		parent::setUp();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Preserves isolated test state before exercising verification.
		$this->original_post = $_POST;
		Functions\when( 'sanitize_text_field' )->alias( static fn( string $value ): string => trim( $value ) );
		Functions\when( 'wp_unslash' )->returnArg();
		Functions\when( 'wp_is_post_revision' )->justReturn( false );
		Functions\when( 'wp_is_post_autosave' )->justReturn( false );
	}

	protected function tearDown(): void {
		$_POST = $this->original_post;
		parent::tearDown();
	}

	public function test_checked_field_enables_static_page_layout(): void {
		$stored = null;
		Functions\when( 'wp_verify_nonce' )->justReturn( 1 );
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'update_post_meta' )->alias(
			static function ( int $post_id, string $meta_key, bool $value ) use ( &$stored ): int {
				$stored = array( $post_id, $meta_key, $value );

				return 1;
			}
		);
		Functions\expect( 'delete_post_meta' )->never();

		$_POST = array(
			'alborsa_platform_static_page_nonce' => 'valid',
			StaticPageMeta::ENABLED              => '1',
		);

		( new StaticPageAdminMetaBox( new StaticPageMeta() ) )->save( 50, $this->page( 50 ) );

		self::assertSame( array( 50, StaticPageMeta::ENABLED, true ), $stored );
	}

	public function test_unchecked_field_removes_static_page_layout(): void {
		$deleted = null;
		Functions\when( 'wp_verify_nonce' )->justReturn( 1 );
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\expect( 'update_post_meta' )->never();
		Functions\when( 'delete_post_meta' )->alias(
			static function ( int $post_id, string $meta_key ) use ( &$deleted ): bool {
				$deleted = array( $post_id, $meta_key );

				return true;
			}
		);

		$_POST = array(
			'alborsa_platform_static_page_nonce' => 'valid',
		);

		( new StaticPageAdminMetaBox( new StaticPageMeta() ) )->save( 50, $this->page( 50 ) );

		self::assertSame( array( 50, StaticPageMeta::ENABLED ), $deleted );
	}

	public function test_invalid_nonce_does_not_change_page_metadata(): void {
		$mutated = false;
		Functions\when( 'wp_verify_nonce' )->justReturn( false );
		Functions\expect( 'current_user_can' )->never();
		Functions\when( 'update_post_meta' )->alias(
			static function () use ( &$mutated ): int {
				$mutated = true;
				return 1;
			}
		);
		Functions\when( 'delete_post_meta' )->alias(
			static function () use ( &$mutated ): bool {
				$mutated = true;
				return true;
			}
		);

		$_POST = array(
			'alborsa_platform_static_page_nonce' => 'invalid',
			StaticPageMeta::ENABLED              => '1',
		);

		( new StaticPageAdminMetaBox( new StaticPageMeta() ) )->save( 50, $this->page( 50 ) );

		self::assertFalse( $mutated );
	}

	private function page( int $post_id ): \WP_Post {
		return new \WP_Post(
			(object) array(
				'ID'        => $post_id,
				'post_type' => 'page',
				'post_name' => 'about',
			)
		);
	}
}

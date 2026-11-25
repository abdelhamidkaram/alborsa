<?php
/**
 * Editorial metadata tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\Editorial;

use Alborsa\Platform\Modules\Editorial\EditorialMeta;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;

final class EditorialMetaTest extends UnitTestCase {
	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'sanitize_text_field' )->alias( static fn( string $value ): string => trim( $value ) );
		Functions\when( 'sanitize_textarea_field' )->alias( static fn( string $value ): string => trim( $value ) );
		Functions\when( 'esc_url_raw' )->alias(
			static fn( string $url ): string => str_starts_with( $url, 'https://' ) ? $url : ''
		);
	}

	public function test_registers_public_editorial_fields_and_private_rss_identity_for_each_post_type(): void {
		$registrations = array();
		Functions\when( 'register_post_meta' )->alias(
			static function ( string $post_type, string $meta_key, array $arguments ) use ( &$registrations ): bool {
				$registrations[ $post_type ][ $meta_key ] = $arguments;

				return true;
			}
		);

		( new EditorialMeta() )->register();

		self::assertCount( 3, $registrations );
		self::assertCount( 7, $registrations['post'] );
		self::assertTrue( $registrations['post'][ EditorialMeta::SOURCE_URL ]['show_in_rest'] );
		self::assertFalse( $registrations['post'][ EditorialMeta::RSS_ITEM_HASH ]['show_in_rest'] );
		self::assertFalse( $registrations['post'][ EditorialMeta::INVESTOR_NOTE ]['show_in_rest'] );
		self::assertArrayNotHasKey( EditorialMeta::INVESTOR_NOTE, $registrations['alb_analysis'] );
		self::assertArrayNotHasKey( EditorialMeta::INVESTOR_NOTE, $registrations['alb_guide'] );
	}

	public function test_sanitizers_reject_invalid_dates_urls_and_hashes(): void {
		$meta = new EditorialMeta();

		self::assertSame( '2026-09-16', $meta->sanitize_date( '2026-09-16' ) );
		self::assertSame( '', $meta->sanitize_date( '2026-02-31' ) );
		self::assertSame( '', $meta->sanitize_http_url( 'javascript:alert(1)' ) );
		self::assertSame( str_repeat( 'a', 64 ), $meta->sanitize_hash( strtoupper( str_repeat( 'a', 64 ) ) ) );
		self::assertSame( '', $meta->sanitize_hash( 'short' ) );
	}
}

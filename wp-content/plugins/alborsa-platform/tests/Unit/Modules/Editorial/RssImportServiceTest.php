<?php
/**
 * RSS draft importer tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\Editorial;

use Alborsa\Platform\Modules\Editorial\EditorialLanguageGateway;
use Alborsa\Platform\Modules\Editorial\EditorialMeta;
use Alborsa\Platform\Modules\Editorial\RssFeedReader;
use Alborsa\Platform\Modules\Editorial\RssImportService;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;
use WP_Error;

final class RssImportServiceTest extends UnitTestCase {
	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'sanitize_text_field' )->alias( static fn( string $value ): string => trim( $value ) );
		Functions\when( 'sanitize_key' )->alias( static fn( string $value ): string => strtolower( $value ) );
		Functions\when( 'wp_kses_post' )->returnArg();
		Functions\when( 'wp_strip_all_tags' )->returnArg();
		Functions\when( 'wp_trim_words' )->returnArg();
		Functions\when( 'get_current_user_id' )->justReturn( 9 );
	}

	public function test_import_creates_only_a_draft_with_source_identity_and_explicit_language(): void {
		$inserted = array();
		$stored   = array();
		Functions\when( 'get_posts' )->justReturn( array() );
		Functions\when( 'wp_insert_post' )->alias(
			static function ( array $post, bool $wp_error ) use ( &$inserted ): int {
				$inserted = $post;
				self::assertTrue( $wp_error );

				return 42;
			}
		);
		Functions\when( 'update_post_meta' )->alias(
			static function ( int $post_id, string $meta_key, mixed $value ) use ( &$stored ): int {
				self::assertSame( 42, $post_id );
				$stored[ $meta_key ] = $value;

				return 1;
			}
		);
		$language = $this->language_gateway();
		$service  = new RssImportService( $this->reader(), $language, new EditorialMeta() );

		$report = $service->import( 'https://example.test/feed', 'Example News', 'ar', 20, false );

		self::assertIsArray( $report );
		self::assertSame( 1, $report['counts']['imported'] );
		self::assertSame( 'draft', $inserted['post_status'] );
		self::assertSame( 'post', $inserted['post_type'] );
		self::assertSame( 'Example News', $stored[ EditorialMeta::SOURCE_NAME ] );
		self::assertSame( 'https://example.test/story', $stored[ EditorialMeta::SOURCE_URL ] );
		self::assertSame( 64, strlen( $stored[ EditorialMeta::RSS_ITEM_HASH ] ) );
		self::assertSame( array( array( 42, 'ar' ) ), $language->assignments );
	}

	public function test_dry_run_prevents_writes_and_deduplicates_the_same_feed_item(): void {
		Functions\when( 'get_posts' )->justReturn( array() );
		Functions\expect( 'wp_insert_post' )->never();
		$reader = $this->reader( 2 );
		$report = ( new RssImportService( $reader, $this->language_gateway(), new EditorialMeta() ) )
			->import( 'https://example.test/feed', 'Example News', 'ar', 20, true );

		self::assertIsArray( $report );
		self::assertSame( 1, $report['counts']['would_import'] );
		self::assertSame( 1, $report['counts']['duplicate'] );
	}

	private function reader( int $repeat = 1 ): RssFeedReader {
		return new class($repeat) implements RssFeedReader {
			public function __construct( private int $repeat ) {
			}

			public function read( string $url, int $limit ): array|WP_Error {
				unset( $url, $limit );
				$item = array(
					'title'   => 'Market story',
					'url'     => 'https://example.test/story',
					'content' => '<p>Summary</p>',
				);

				return array_fill( 0, $this->repeat, $item );
			}
		};
	}

	private function language_gateway(): EditorialLanguageGateway {
		return new class() implements EditorialLanguageGateway {
			/** @var list<array{int, string}> */
			public array $assignments = array();

			public function available(): bool {
				return true;
			}

			public function has_language( string $language ): bool {
				return 'ar' === $language;
			}

			public function set_post_language( int $post_id, string $language ): void {
				$this->assignments[] = array( $post_id, $language );
			}
		};
	}
}

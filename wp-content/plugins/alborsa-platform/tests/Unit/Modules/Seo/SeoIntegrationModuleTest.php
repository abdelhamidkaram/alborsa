<?php
/**
 * Yoast SEO integration tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\Seo;

use Alborsa\Platform\Modules\Editorial\EditorialMeta;
use Alborsa\Platform\Modules\Editorial\EditorialModule;
use Alborsa\Platform\Modules\Seo\SeoIntegrationModule;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;

final class SeoIntegrationModuleTest extends UnitTestCase {
	public function test_registration_extends_yoast_without_printing_an_independent_graph(): void {
		( new SeoIntegrationModule() )->register();

		self::assertSame( 10, has_filter( 'wpseo_schema_article_post_types', SeoIntegrationModule::class . '->article_post_types()' ) );
		self::assertSame( 10, has_filter( 'wpseo_schema_article', SeoIntegrationModule::class . '->article_schema()' ) );
		self::assertSame( 10, has_filter( 'wpseo_schema_webpage', SeoIntegrationModule::class . '->webpage_schema()' ) );
	}

	public function test_article_types_include_analysis_and_guide_once(): void {
		$types = ( new SeoIntegrationModule() )->article_post_types( array( 'post', EditorialModule::ANALYSIS_POST_TYPE ) );

		self::assertSame( array( 'post', EditorialModule::ANALYSIS_POST_TYPE, EditorialModule::GUIDE_POST_TYPE ), $types );
	}

	public function test_schema_uses_valid_source_reviewer_and_review_date_metadata(): void {
		Functions\when( 'get_queried_object_id' )->justReturn( 25 );
		Functions\when( 'esc_url_raw' )->returnArg();
		Functions\when( 'absint' )->alias( static fn( mixed $value ): int => abs( (int) $value ) );
		Functions\when( 'get_post_meta' )->alias(
			static function ( int $post_id, string $meta_key ): int|string {
				self::assertSame( 25, $post_id );

				return match ( $meta_key ) {
					EditorialMeta::SOURCE_URL       => 'https://example.test/source',
					EditorialMeta::REVIEWER_ID      => 7,
					EditorialMeta::LAST_REVIEWED_AT => '2026-09-16',
					default                          => '',
				};
			}
		);
		Functions\when( 'get_userdata' )->alias(
			static fn( int $user_id ): \WP_User => new \WP_User(
				(object) array(
					'ID'           => $user_id,
					'display_name' => 'Editorial Reviewer',
				)
			)
		);
		$module  = new SeoIntegrationModule();
		$article = $module->article_schema( array( '@type' => 'Article' ) );
		$webpage = $module->webpage_schema( array( '@type' => 'WebPage' ) );

		self::assertSame( 'https://example.test/source', $article['citation'] );
		self::assertSame( 'Editorial Reviewer', $webpage['reviewedBy']['name'] );
		self::assertSame( '2026-09-16', $webpage['lastReviewed'] );
	}
}

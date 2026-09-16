<?php
/**
 * Content-to-asset relationship service tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Shared\Relationships;

use Alborsa\Platform\Modules\Assets\AssetsModule;
use Alborsa\Platform\Modules\Editorial\EditorialModule;
use Alborsa\Platform\Shared\Relationships\ContentAssetRelationshipService;
use Alborsa\Platform\Shared\Relationships\RelationshipRepository;
use Alborsa\Platform\Tests\Support\WpdbDouble;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\DataProvider;

final class ContentAssetRelationshipServiceTest extends UnitTestCase {
	#[DataProvider( 'supported_content_cases' )]
	public function test_supported_content_can_be_linked_to_an_asset( string $content_type ): void {
		$wpdb            = new WpdbDouble();
		$wpdb->insert_id = 23;
		Functions\when( 'current_time' )->justReturn( '2026-09-16 16:00:00' );
		$service = $this->service( $wpdb );

		$relationship_id = $service->link(
			$this->post( 10, $content_type ),
			$this->post( 20, AssetsModule::POST_TYPE ),
			ContentAssetRelationshipService::MENTIONS,
			4
		);

		self::assertSame( 23, $relationship_id );
		self::assertSame( $content_type, $wpdb->last_insert_data['from_object_type'] );
		self::assertSame( AssetsModule::POST_TYPE, $wpdb->last_insert_data['to_object_type'] );
		self::assertSame( 4, $wpdb->last_insert_data['sort_order'] );
	}

	/**
	 * @return iterable<string, array{string}>
	 */
	public static function supported_content_cases(): iterable {
		yield 'news' => array( 'post' );
		yield 'analysis' => array( EditorialModule::ANALYSIS_POST_TYPE );
		yield 'guide' => array( EditorialModule::GUIDE_POST_TYPE );
	}

	#[DataProvider( 'invalid_relationship_cases' )]
	public function test_link_rejects_invalid_relationship(
		string $content_type,
		string $asset_type,
		string $relation_type,
		string $expected_error
	): void {
		$service     = $this->service( new WpdbDouble() );
		$link_result = $service->link(
			$this->post( 10, $content_type ),
			$this->post( 20, $asset_type ),
			$relation_type,
			0
		);

		self::assertTrue( is_wp_error( $link_result ) );
		self::assertSame( $expected_error, $link_result->get_error_code() );
	}

	/**
	 * @return iterable<string, array{string, string, string, string}>
	 */
	public static function invalid_relationship_cases(): iterable {
		yield 'unsupported content' => array(
			'page',
			AssetsModule::POST_TYPE,
			ContentAssetRelationshipService::MENTIONS,
			'alborsa_platform_relationship_content_not_allowed',
		);
		yield 'non-asset target' => array(
			'post',
			'post',
			ContentAssetRelationshipService::MENTIONS,
			'alborsa_platform_relationship_asset_required',
		);
		yield 'unsupported relation' => array(
			'post',
			AssetsModule::POST_TYPE,
			'recommends',
			'alborsa_platform_relationship_type_not_allowed',
		);
	}

	public function test_for_content_reads_source_relationships(): void {
		$service = $this->reading_service();

		$content_relationships = $service->for_content( $this->post( 10, 'post' ) );

		self::assertSame( array( array( 'relation_type' => 'mentions' ) ), $content_relationships );
	}

	public function test_for_asset_reads_target_relationships(): void {
		$service = $this->reading_service();

		$asset_relationships = $service->for_asset( $this->post( 20, AssetsModule::POST_TYPE ) );

		self::assertSame( array( array( 'relation_type' => 'mentions' ) ), $asset_relationships );
	}

	public function test_for_asset_rejects_non_asset_lookup(): void {
		$service = $this->service( new WpdbDouble() );

		$invalid_lookup = $service->for_asset( $this->post( 10, 'post' ) );

		self::assertSame( 'alborsa_platform_relationship_asset_required', $invalid_lookup->get_error_code() );
	}

	public function test_unlink_deletes_the_exact_content_asset_relationship(): void {
		$wpdb    = new WpdbDouble();
		$service = $this->service( $wpdb );

		$deleted = $service->unlink(
			$this->post( 10, EditorialModule::ANALYSIS_POST_TYPE ),
			$this->post( 20, AssetsModule::POST_TYPE ),
			ContentAssetRelationshipService::ANALYZES
		);

		self::assertTrue( $deleted );
		self::assertStringContainsString( 'from_object_id = 10', (string) $wpdb->last_query );
		self::assertStringContainsString( "relation_type = 'analyzes'", (string) $wpdb->last_query );
	}

	private function service( WpdbDouble $wpdb ): ContentAssetRelationshipService {
		return new ContentAssetRelationshipService( new RelationshipRepository( $wpdb ) );
	}

	private function reading_service(): ContentAssetRelationshipService {
		$wpdb = new class() extends WpdbDouble {
			public function get_results( string $query, string|int $output = 'OBJECT' ): ?array {
				parent::get_results( $query, $output );

				return array( array( 'relation_type' => 'mentions' ) );
			}
		};

		return $this->service( $wpdb );
	}

	private function post( int $post_id, string $post_type ): \WP_Post {
		return new \WP_Post(
			(object) array(
				'ID'        => $post_id,
				'post_type' => $post_type,
				'post_name' => 'fixture',
			)
		);
	}
}

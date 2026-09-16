<?php
/**
 * Asset presenter tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\Assets;

use Alborsa\Platform\Modules\Assets\AssetMeta;
use Alborsa\Platform\Modules\Assets\AssetPresenter;
use Alborsa\Platform\Modules\Assets\AssetsModule;
use Alborsa\Platform\Shared\Relationships\ContentAssetRelationshipService;
use Alborsa\Platform\Shared\Relationships\RelationshipRepository;
use Alborsa\Platform\Tests\Support\WpdbDouble;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;

final class AssetPresenterTest extends UnitTestCase {
	public function test_summary_contains_only_public_asset_fields(): void {
		$this->stub_public_asset_functions();
		$presenter = $this->presenter( new WpdbDouble() );

		$summary = $presenter->summary( $this->post( 20, AssetsModule::POST_TYPE, 'aramco' ) );

		self::assertSame( 20, $summary['id'] );
		self::assertSame( 'aramco', $summary['slug'] );
		self::assertSame( 'Asset 20', $summary['title'] );
		self::assertSame( '2222', $summary['canonical_symbol'] );
		self::assertSame( 'SAR', $summary['base_currency'] );
		self::assertSame( array( 'stock' ), $summary['asset_types'] );
		self::assertArrayNotHasKey( AssetMeta::PROVIDER_MAPPINGS, $summary );
	}

	public function test_detail_includes_only_published_supported_related_content(): void {
		$this->stub_public_asset_functions();
		Functions\when( 'wp_kses_post' )->returnArg();

		$wpdb = new class() extends WpdbDouble {
			public function get_results( string $query, string|int $output = 'OBJECT' ): ?array {
				parent::get_results( $query, $output );

				return array(
					array(
						'from_object_id' => '10',
						'relation_type'  => 'mentions',
					),
					array(
						'from_object_id' => '11',
						'relation_type'  => 'analyzes',
					),
				);
			}
		};
		Functions\when( 'get_posts' )->alias(
			fn(): array => array( $this->post( 10, 'post', 'content-10' ) )
		);

		$detail = $this->presenter( $wpdb )->detail(
			$this->post( 20, AssetsModule::POST_TYPE, 'aramco', 'publish', '<p>Profile</p>' )
		);

		self::assertSame( '<p>Profile</p>', $detail['content'] );
		self::assertCount( 1, $detail['related_content'] );
		self::assertSame( 10, $detail['related_content'][0]['id'] );
		self::assertSame( 'mentions', $detail['related_content'][0]['relation_type'] );
	}

	public function test_relationship_storage_error_is_replaced_with_public_safe_error(): void {
		$this->stub_public_asset_functions();
		$wpdb = new class() extends WpdbDouble {
			public function get_results( string $query, string|int $output = 'OBJECT' ): ?array {
				parent::get_results( $query, $output );
				$this->last_error = 'Sensitive database detail';

				return null;
			}
		};

		$detail = $this->presenter( $wpdb )->detail( $this->post( 20, AssetsModule::POST_TYPE, 'aramco' ) );

		self::assertTrue( is_wp_error( $detail ) );
		self::assertSame( 'alborsa_platform_asset_relationships_unavailable', $detail->get_error_code() );
		self::assertStringNotContainsString( 'Sensitive database detail', $detail->get_error_message() );
		self::assertSame( array( 'status' => 503 ), $detail->get_error_data() );
	}

	private function presenter( WpdbDouble $wpdb ): AssetPresenter {
		$relationships = new ContentAssetRelationshipService( new RelationshipRepository( $wpdb ) );

		return new AssetPresenter( $relationships );
	}

	private function stub_public_asset_functions(): void {
		Functions\when( 'get_the_terms' )->alias(
			static function ( int $post_id, string $taxonomy ): array {
				unset( $post_id );

				return AssetsModule::TYPE_TAXONOMY === $taxonomy
					? array( new \WP_Term( (object) array( 'slug' => 'stock' ) ) )
					: array();
			}
		);
		Functions\when( 'get_the_title' )->alias(
			static fn( \WP_Post $post ): string => 'Asset ' . $post->ID
		);
		Functions\when( 'get_the_excerpt' )->justReturn( '<p>Public excerpt</p>' );
		Functions\when( 'wp_strip_all_tags' )->alias(
			static fn( string $text ): string => preg_replace( '/<[^>]+>/', '', $text ) ?? ''
		);
		Functions\when( 'get_permalink' )->alias(
			static fn( \WP_Post $post ): string => 'https://example.test/' . $post->post_name . '/'
		);
		Functions\when( 'esc_url_raw' )->returnArg();
		Functions\when( 'sanitize_text_field' )->alias(
			static fn( string $text ): string => trim( $text )
		);
		Functions\when( 'sanitize_key' )->alias(
			static fn( string $key ): string => preg_replace( '/[^a-z0-9_-]/', '', strtolower( $key ) ) ?? ''
		);
		Functions\when( 'get_post_meta' )->alias(
			static function ( int $post_id, string $meta_key ): string {
				unset( $post_id );
				$values = array(
					AssetMeta::CANONICAL_SYMBOL => '2222',
					AssetMeta::BASE_CURRENCY    => 'SAR',
				);

				return $values[ $meta_key ] ?? '';
			}
		);
	}

	private function post(
		int $post_id,
		string $post_type,
		string $slug,
		string $status = 'publish',
		string $content = ''
	): \WP_Post {
		return new \WP_Post(
			(object) array(
				'ID'           => $post_id,
				'post_type'    => $post_type,
				'post_name'    => $slug,
				'post_status'  => $status,
				'post_content' => $content,
			)
		);
	}
}

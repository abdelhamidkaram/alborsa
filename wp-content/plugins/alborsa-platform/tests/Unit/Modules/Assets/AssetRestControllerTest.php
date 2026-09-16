<?php
/**
 * Asset REST controller tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\Assets;

use Alborsa\Platform\Modules\Assets\AssetPresenter;
use Alborsa\Platform\Modules\Assets\AssetRestController;
use Alborsa\Platform\Modules\Assets\AssetsModule;
use Alborsa\Platform\Shared\Relationships\ContentAssetRelationshipService;
use Alborsa\Platform\Shared\Relationships\RelationshipRepository;
use Alborsa\Platform\Tests\Support\WpdbDouble;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;

final class AssetRestControllerTest extends UnitTestCase {
	public function test_registration_exposes_read_only_collection_and_single_routes(): void {
		$routes = array();

		Functions\when( 'register_rest_route' )->alias(
			static function ( string $route_namespace, string $route, array $arguments ) use ( &$routes ): bool {
				$routes[ $route ] = array(
					'namespace' => $route_namespace,
					'arguments' => $arguments,
				);

				return true;
			}
		);

		$this->controller()->register_routes();

		self::assertSame( array( '/assets', '/assets/(?P<id>[1-9][0-9]*)' ), array_keys( $routes ) );
		self::assertSame( AssetRestController::NAMESPACE, $routes['/assets']['namespace'] );
		self::assertSame( 'GET', $routes['/assets']['arguments']['methods'] );
		self::assertSame( '__return_true', $routes['/assets']['arguments']['permission_callback'] );
		self::assertSame( 'GET', $routes['/assets/(?P<id>[1-9][0-9]*)']['arguments']['methods'] );
	}

	public function test_collection_caps_pagination_and_applies_public_filters(): void {
		$query_arguments = array();
		$this->stub_presenter_functions();
		Functions\when( 'get_posts' )->alias(
			function ( array $arguments ) use ( &$query_arguments ): array {
				$query_arguments = $arguments;

				return array( $this->post( 20, 'aramco' ) );
			}
		);

		$response = $this->controller()->get_collection(
			new \WP_REST_Request(
				array(
					'page'       => '9999',
					'per_page'   => '999',
					'search'     => ' energy ',
					'asset_type' => 'Stock',
					'exchange'   => 'Tadawul',
				)
			)
		);

		self::assertSame( AssetsModule::POST_TYPE, $query_arguments['post_type'] );
		self::assertSame( 'publish', $query_arguments['post_status'] );
		self::assertSame( 100, $query_arguments['posts_per_page'] );
		self::assertSame( 1000, $query_arguments['paged'] );
		self::assertSame( 'energy', $query_arguments['s'] );
		self::assertSame( AssetsModule::TYPE_TAXONOMY, $query_arguments['tax_query'][0]['taxonomy'] );
		self::assertSame( array( 'stock' ), $query_arguments['tax_query'][0]['terms'] );
		self::assertSame( AssetsModule::EXCHANGE_TAXONOMY, $query_arguments['tax_query'][1]['taxonomy'] );
		self::assertSame( 20, $response['items'][0]['id'] );
		self::assertSame(
			array(
				'page'     => 1000,
				'per_page' => 100,
			),
			$response['pagination']
		);
	}

	public function test_single_route_hides_draft_asset(): void {
		Functions\when( 'get_post' )->alias(
			fn( int $post_id ): \WP_Post => $this->post( $post_id, 'draft-asset', 'draft' )
		);

		$response = $this->controller()->get_single( new \WP_REST_Request( array( 'id' => 20 ) ) );

		self::assertTrue( is_wp_error( $response ) );
		self::assertSame( 'alborsa_platform_asset_not_found', $response->get_error_code() );
		self::assertSame( array( 'status' => 404 ), $response->get_error_data() );
	}

	public function test_single_route_hides_non_asset_post(): void {
		Functions\when( 'get_post' )->alias(
			static fn( int $post_id ): \WP_Post => new \WP_Post(
				(object) array(
					'ID'           => $post_id,
					'post_type'    => 'post',
					'post_name'    => 'news',
					'post_status'  => 'publish',
					'post_content' => '',
				)
			)
		);

		$response = $this->controller()->get_single( new \WP_REST_Request( array( 'id' => 20 ) ) );

		self::assertTrue( is_wp_error( $response ) );
		self::assertSame( 'alborsa_platform_asset_not_found', $response->get_error_code() );
	}

	private function controller(): AssetRestController {
		$relationships = new ContentAssetRelationshipService( new RelationshipRepository( new WpdbDouble() ) );

		return new AssetRestController( new AssetPresenter( $relationships ) );
	}

	private function stub_presenter_functions(): void {
		Functions\when( 'sanitize_text_field' )->alias(
			static fn( string $text ): string => trim( $text )
		);
		Functions\when( 'sanitize_key' )->alias(
			static fn( string $key ): string => preg_replace( '/[^a-z0-9_-]/', '', strtolower( $key ) ) ?? ''
		);
		Functions\when( 'get_the_terms' )->justReturn( array() );
		Functions\when( 'get_the_title' )->justReturn( 'Saudi Aramco' );
		Functions\when( 'get_the_excerpt' )->justReturn( 'Energy company' );
		Functions\when( 'wp_strip_all_tags' )->returnArg();
		Functions\when( 'get_permalink' )->justReturn( 'https://example.test/aswaq/tadawul/2222/' );
		Functions\when( 'esc_url_raw' )->returnArg();
		Functions\when( 'get_post_meta' )->justReturn( '' );
	}

	private function post( int $post_id, string $slug, string $status = 'publish' ): \WP_Post {
		return new \WP_Post(
			(object) array(
				'ID'           => $post_id,
				'post_type'    => AssetsModule::POST_TYPE,
				'post_name'    => $slug,
				'post_status'  => $status,
				'post_content' => '',
			)
		);
	}
}

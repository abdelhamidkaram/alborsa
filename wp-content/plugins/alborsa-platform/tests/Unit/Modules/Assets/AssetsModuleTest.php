<?php
/**
 * Assets module tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\Assets;

use Alborsa\Platform\Modules\Assets\AssetsModule;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\DataProvider;

final class AssetsModuleTest extends UnitTestCase {
	public function test_registration_defers_content_model_until_init(): void {
		Functions\expect( 'register_post_type' )->never();
		Functions\expect( 'register_taxonomy' )->never();
		Functions\expect( 'register_post_meta' )->never();
		Functions\expect( 'add_rewrite_rule' )->never();

		$module = new AssetsModule();
		$module->register();

		self::assertSame( 10, has_action( 'init', AssetsModule::class . '->initialize()' ) );
		self::assertSame( 10, has_filter( 'post_type_link', AssetsModule::class . '->asset_permalink()' ) );
		self::assertSame( 10, has_filter( 'query_vars', AssetsModule::class . '->register_route_query_var()' ) );
		self::assertSame( 10, has_filter( 'request', AssetsModule::class . '->constrain_asset_route()' ) );
	}

	public function test_initialization_registers_asset_content_model_and_routes(): void {
		$registered_post_types = array();
		$registered_taxonomies = array();
		$rewrite_rules         = array();

		Functions\when( 'register_post_type' )->alias(
			static function ( string $post_type, array $arguments ) use ( &$registered_post_types ): void {
				$registered_post_types[ $post_type ] = $arguments;
			}
		);
		Functions\when( 'register_taxonomy' )->alias(
			static function ( string $taxonomy, array $object_types, array $arguments ) use ( &$registered_taxonomies ): void {
				$registered_taxonomies[ $taxonomy ] = array(
					'object_types' => $object_types,
					'arguments'    => $arguments,
				);
			}
		);
		Functions\when( 'add_rewrite_rule' )->alias(
			static function ( string $regex, string $query, string $position ) use ( &$rewrite_rules ): void {
				$rewrite_rules[ $regex ] = array( $query, $position );
			}
		);
		Functions\when( 'register_post_meta' )->justReturn( true );

		( new AssetsModule() )->initialize();

		self::assertSame( array( AssetsModule::POST_TYPE ), array_keys( $registered_post_types ) );
		$this->assert_asset_post_type( $registered_post_types[ AssetsModule::POST_TYPE ] );
		$this->assert_asset_taxonomies( $registered_taxonomies );
		self::assertSame(
			array(
				'^aswaq/tadawul/([^/]+)/?$' => array( 'index.php?post_type=alb_asset&name=$matches[1]&alb_asset_route=tadawul', 'top' ),
				'^forex/([^/]+)/?$'         => array( 'index.php?post_type=alb_asset&name=$matches[1]&alb_asset_route=forex', 'top' ),
			),
			$rewrite_rules
		);
	}

	public function test_asset_route_query_variable_is_registered(): void {
		$query_vars = ( new AssetsModule() )->register_route_query_var( array( 'name' ) );

		self::assertSame( array( 'name', AssetsModule::ROUTE_QUERY_VAR ), $query_vars );
	}

	/**
	 * @param array<string|int, array<string, mixed>|string> $expected_tax_query Expected taxonomy constraint.
	 */
	#[DataProvider( 'route_constraint_cases' )]
	public function test_asset_route_is_constrained_to_its_classification(
		string $route,
		array $expected_tax_query
	): void {
		Functions\when( 'sanitize_key' )->returnArg();

		$query_vars = ( new AssetsModule() )->constrain_asset_route(
			array(
				'post_type'                   => AssetsModule::POST_TYPE,
				AssetsModule::ROUTE_QUERY_VAR => $route,
			)
		);

		self::assertSame( $expected_tax_query, $query_vars['tax_query'] );
	}

	/**
	 * @return iterable<string, array{string, array<string|int, array<string, mixed>|string>}>
	 */
	public static function route_constraint_cases(): iterable {
		yield 'forex' => array(
			'forex',
			array(
				array(
					'taxonomy' => AssetsModule::TYPE_TAXONOMY,
					'field'    => 'slug',
					'terms'    => array( AssetsModule::FOREX_TYPE_SLUG ),
				),
			),
		);
		yield 'Tadawul' => array(
			'tadawul',
			array(
				'relation' => 'AND',
				array(
					'taxonomy' => AssetsModule::TYPE_TAXONOMY,
					'field'    => 'slug',
					'terms'    => array( AssetsModule::STOCK_TYPE_SLUG ),
				),
				array(
					'taxonomy' => AssetsModule::EXCHANGE_TAXONOMY,
					'field'    => 'slug',
					'terms'    => array( AssetsModule::TADAWUL_EXCHANGE_SLUG ),
				),
			),
		);
	}

	/**
	 * @param list<string> $assigned_terms Taxonomy and term pairs.
	 */
	#[DataProvider( 'approved_route_cases' )]
	public function test_approved_asset_classification_generates_expected_permalink(
		array $assigned_terms,
		string $asset_slug,
		string $expected_path
	): void {
		Functions\when( 'has_term' )->alias(
			static function ( string $term, string $taxonomy ) use ( $assigned_terms ): bool {
				return in_array( $taxonomy . ':' . $term, $assigned_terms, true );
			}
		);
		Functions\when( 'user_trailingslashit' )->alias(
			static fn( string $path ): string => rtrim( $path, '/' ) . '/'
		);
		Functions\when( 'home_url' )->alias(
			static fn( string $path ): string => 'https://example.test/' . ltrim( $path, '/' )
		);

		$permalink = ( new AssetsModule() )->asset_permalink(
			'https://example.test/?post_type=alb_asset&p=7',
			$this->asset_post( $asset_slug ),
			false
		);

		self::assertSame( 'https://example.test/' . $expected_path, $permalink );
	}

	/**
	 * @return iterable<string, array{list<string>, string, string}>
	 */
	public static function approved_route_cases(): iterable {
		yield 'forex pair' => array(
			array( AssetsModule::TYPE_TAXONOMY . ':' . AssetsModule::FOREX_TYPE_SLUG ),
			'sar-usd',
			'forex/sar-usd/',
		);
		yield 'Tadawul stock' => array(
			array(
				AssetsModule::TYPE_TAXONOMY . ':' . AssetsModule::STOCK_TYPE_SLUG,
				AssetsModule::EXCHANGE_TAXONOMY . ':' . AssetsModule::TADAWUL_EXCHANGE_SLUG,
			),
			'2222',
			'aswaq/tadawul/2222/',
		);
	}

	public function test_asset_without_approved_classification_keeps_query_permalink(): void {
		Functions\when( 'has_term' )->justReturn( false );

		$query_permalink = 'https://example.test/?post_type=alb_asset&p=7';
		$permalink       = ( new AssetsModule() )->asset_permalink(
			$query_permalink,
			$this->asset_post( 'generic-asset' ),
			false
		);

		self::assertSame( $query_permalink, $permalink );
	}

	/**
	 * @param array<string, mixed> $arguments Registered post type arguments.
	 */
	private function assert_asset_post_type( array $arguments ): void {
		self::assertTrue( $arguments['public'] );
		self::assertFalse( $arguments['has_archive'] );
		self::assertFalse( $arguments['rewrite'] );
		self::assertTrue( $arguments['show_ui'] );
		self::assertTrue( $arguments['show_in_rest'] );
		self::assertSame( array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'revisions', 'custom-fields' ), $arguments['supports'] );
		self::assertFalse( $arguments['hierarchical'] );
		self::assertSame( array( 'alb_content', 'alb_contents' ), $arguments['capability_type'] );
		self::assertTrue( $arguments['map_meta_cap'] );
		self::assertNotContains( '', $arguments['labels'] );
	}

	/**
	 * @param array<string, array{object_types: list<string>, arguments: array<string, mixed>}> $taxonomies Registrations.
	 */
	private function assert_asset_taxonomies( array $taxonomies ): void {
		self::assertSame(
			array( AssetsModule::TYPE_TAXONOMY, AssetsModule::EXCHANGE_TAXONOMY, AssetsModule::SECTOR_TAXONOMY ),
			array_keys( $taxonomies )
		);

		foreach ( $taxonomies as $registration ) {
			self::assertSame( array( AssetsModule::POST_TYPE ), $registration['object_types'] );
			self::assertTrue( $registration['arguments']['public'] );
			self::assertFalse( $registration['arguments']['publicly_queryable'] );
			self::assertTrue( $registration['arguments']['show_ui'] );
			self::assertTrue( $registration['arguments']['show_in_rest'] );
			self::assertTrue( $registration['arguments']['show_admin_column'] );
			self::assertFalse( $registration['arguments']['show_in_nav_menus'] );
			self::assertTrue( $registration['arguments']['hierarchical'] );
			self::assertFalse( $registration['arguments']['query_var'] );
			self::assertFalse( $registration['arguments']['rewrite'] );
			self::assertNotContains( '', $registration['arguments']['labels'] );
		}
	}

	private function asset_post( string $slug ): \WP_Post {
		return new \WP_Post(
			(object) array(
				'ID'        => 7,
				'post_type' => AssetsModule::POST_TYPE,
				'post_name' => $slug,
			)
		);
	}
}

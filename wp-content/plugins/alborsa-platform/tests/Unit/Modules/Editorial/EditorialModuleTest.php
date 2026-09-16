<?php
/**
 * Editorial module tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\Editorial;

use Alborsa\Platform\Modules\Editorial\EditorialModule;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;

final class EditorialModuleTest extends UnitTestCase {
	private const LABEL_KEYS = array(
		'name',
		'singular_name',
		'add_new',
		'add_new_item',
		'edit_item',
		'new_item',
		'view_item',
		'view_items',
		'search_items',
		'not_found',
		'not_found_in_trash',
		'parent_item_colon',
		'all_items',
		'archives',
		'attributes',
		'insert_into_item',
		'uploaded_to_this_item',
		'featured_image',
		'set_featured_image',
		'remove_featured_image',
		'use_featured_image',
		'menu_name',
		'filter_items_list',
		'filter_by_date',
		'items_list_navigation',
		'items_list',
		'item_published',
		'item_published_privately',
		'item_reverted_to_draft',
		'item_trashed',
		'item_scheduled',
		'item_updated',
		'item_link',
		'item_link_description',
	);

	private const TAXONOMY_LABEL_KEYS = array(
		'name',
		'singular_name',
		'search_items',
		'all_items',
		'edit_item',
		'update_item',
		'add_new_item',
		'new_item_name',
		'menu_name',
	);

	public function test_registration_defers_post_types_until_init(): void {
		Functions\expect( 'register_post_type' )->never();
		Functions\expect( 'register_taxonomy' )->never();

		$module = new EditorialModule();
		$module->register();

		self::assertSame( 10, has_action( 'init', EditorialModule::class . '->initialize()' ) );
	}

	public function test_initialization_registers_editorial_content_model(): void {
		$registered_post_types = array();
		$registered_taxonomies = array();

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
		( new EditorialModule() )->initialize();

		self::assertSame(
			array( EditorialModule::ANALYSIS_POST_TYPE, EditorialModule::GUIDE_POST_TYPE ),
			array_keys( $registered_post_types )
		);
		$this->assert_post_type_arguments( 'tahleel', $registered_post_types[ EditorialModule::ANALYSIS_POST_TYPE ] );
		$this->assert_post_type_arguments( 'taleem', $registered_post_types[ EditorialModule::GUIDE_POST_TYPE ] );

		self::assertSame(
			array(
				EditorialModule::TOPIC_TAXONOMY,
				EditorialModule::GUIDE_LEVEL_TAXONOMY,
				EditorialModule::GUIDE_TRACK_TAXONOMY,
			),
			array_keys( $registered_taxonomies )
		);
		self::assertSame(
			array( 'post', EditorialModule::ANALYSIS_POST_TYPE, EditorialModule::GUIDE_POST_TYPE ),
			$registered_taxonomies[ EditorialModule::TOPIC_TAXONOMY ]['object_types']
		);
		self::assertSame(
			array( EditorialModule::GUIDE_POST_TYPE ),
			$registered_taxonomies[ EditorialModule::GUIDE_LEVEL_TAXONOMY ]['object_types']
		);
		self::assertSame(
			array( EditorialModule::GUIDE_POST_TYPE ),
			$registered_taxonomies[ EditorialModule::GUIDE_TRACK_TAXONOMY ]['object_types']
		);

		foreach ( $registered_taxonomies as $registration ) {
			$this->assert_taxonomy_arguments( $registration['arguments'] );
		}
	}

	/**
	 * @param array<string, mixed> $arguments Registered post type arguments.
	 */
	private function assert_post_type_arguments( string $rewrite_slug, array $arguments ): void {
		self::assertTrue( $arguments['public'] );
		self::assertTrue( $arguments['has_archive'] );
		self::assertSame(
			array(
				'slug'       => $rewrite_slug,
				'with_front' => false,
			),
			$arguments['rewrite']
		);
		self::assertTrue( $arguments['show_ui'] );
		self::assertTrue( $arguments['show_in_rest'] );
		self::assertSame( array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'revisions' ), $arguments['supports'] );
		self::assertFalse( $arguments['hierarchical'] );
		self::assertSame( array( 'alb_content', 'alb_contents' ), $arguments['capability_type'] );
		self::assertTrue( $arguments['map_meta_cap'] );
		self::assertSame( self::LABEL_KEYS, array_keys( $arguments['labels'] ) );
		self::assertNotContains( '', $arguments['labels'] );
	}

	/**
	 * @param array<string, mixed> $arguments Registered taxonomy arguments.
	 */
	private function assert_taxonomy_arguments( array $arguments ): void {
		self::assertTrue( $arguments['public'] );
		self::assertFalse( $arguments['publicly_queryable'] );
		self::assertTrue( $arguments['show_ui'] );
		self::assertTrue( $arguments['show_in_rest'] );
		self::assertTrue( $arguments['show_admin_column'] );
		self::assertFalse( $arguments['show_in_nav_menus'] );
		self::assertTrue( $arguments['hierarchical'] );
		self::assertFalse( $arguments['query_var'] );
		self::assertFalse( $arguments['rewrite'] );
		self::assertSame( self::TAXONOMY_LABEL_KEYS, array_keys( $arguments['labels'] ) );
		self::assertNotContains( '', $arguments['labels'] );
	}
}

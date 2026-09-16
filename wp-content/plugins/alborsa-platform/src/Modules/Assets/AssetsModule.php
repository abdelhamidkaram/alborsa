<?php
/**
 * Financial asset registration and approved permalink routes.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\Assets;

use Alborsa\Platform\Foundation\Module;
use Alborsa\Platform\Shared\Relationships\ContentAssetRelationshipService;
use Alborsa\Platform\Shared\Relationships\RelationshipRepository;

defined( 'ABSPATH' ) || exit;

final class AssetsModule implements Module {
	public const POST_TYPE             = 'alb_asset';
	public const TYPE_TAXONOMY         = 'alb_asset_type';
	public const EXCHANGE_TAXONOMY     = 'alb_exchange';
	public const SECTOR_TAXONOMY       = 'alb_sector';
	public const STOCK_TYPE_SLUG       = 'stock';
	public const FOREX_TYPE_SLUG       = 'forex';
	public const TADAWUL_EXCHANGE_SLUG = 'tadawul';
	public const ROUTE_QUERY_VAR       = 'alb_asset_route';

	public function register(): void {
		add_action( 'init', array( $this, 'initialize' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_filter( 'post_type_link', array( $this, 'asset_permalink' ), 10, 3 );
		add_filter( 'query_vars', array( $this, 'register_route_query_var' ) );
		add_filter( 'request', array( $this, 'constrain_asset_route' ) );
	}

	public function register_rest_routes(): void {
		global $wpdb;

		if ( ! $wpdb instanceof \wpdb ) {
			return;
		}

		$relationships = new ContentAssetRelationshipService( new RelationshipRepository( $wpdb ) );
		( new AssetRestController( new AssetPresenter( $relationships ) ) )->register_routes();
	}

	public function initialize(): void {
		$this->register_post_type();
		$this->register_taxonomies();
		( new AssetMeta() )->register();
		$this->register_rewrite_rules();
	}

	public function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => $this->post_type_labels(),
				'public'          => true,
				'has_archive'     => false,
				'rewrite'         => false,
				'show_ui'         => true,
				'show_in_rest'    => true,
				'supports'        => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'revisions', 'custom-fields' ),
				'hierarchical'    => false,
				'capability_type' => array( 'alb_content', 'alb_contents' ),
				'map_meta_cap'    => true,
			)
		);
	}

	public function register_taxonomies(): void {
		register_taxonomy(
			self::TYPE_TAXONOMY,
			array( self::POST_TYPE ),
			$this->taxonomy_arguments( $this->asset_type_labels() )
		);

		register_taxonomy(
			self::EXCHANGE_TAXONOMY,
			array( self::POST_TYPE ),
			$this->taxonomy_arguments( $this->exchange_labels() )
		);

		register_taxonomy(
			self::SECTOR_TAXONOMY,
			array( self::POST_TYPE ),
			$this->taxonomy_arguments( $this->sector_labels() )
		);
	}

	public function register_rewrite_rules(): void {
		$query_prefix = 'index.php?post_type=' . self::POST_TYPE . '&name=$matches[1]&' . self::ROUTE_QUERY_VAR . '=';

		add_rewrite_rule( '^aswaq/tadawul/([^/]+)/?$', $query_prefix . 'tadawul', 'top' );
		add_rewrite_rule( '^forex/([^/]+)/?$', $query_prefix . 'forex', 'top' );
	}

	/**
	 * @param list<string> $query_vars Public WordPress query variables.
	 * @return list<string>
	 */
	public function register_route_query_var( array $query_vars ): array {
		$query_vars[] = self::ROUTE_QUERY_VAR;

		return $query_vars;
	}

	/**
	 * @param array<string, mixed> $query_vars Parsed request query variables.
	 * @return array<string, mixed>
	 */
	public function constrain_asset_route( array $query_vars ): array {
		$route = sanitize_key( (string) ( $query_vars[ self::ROUTE_QUERY_VAR ] ?? '' ) );

		if ( self::POST_TYPE !== ( $query_vars['post_type'] ?? '' ) ) {
			return $query_vars;
		}

		if ( 'forex' === $route ) {
			$query_vars['tax_query'] = $this->forex_tax_query();
		} elseif ( 'tadawul' === $route ) {
			$query_vars['tax_query'] = $this->tadawul_tax_query();
		}

		return $query_vars;
	}

	public function asset_permalink( string $post_link, \WP_Post $asset, bool $leave_name ): string {
		if ( self::POST_TYPE !== $asset->post_type ) {
			return $post_link;
		}

		$slug         = $leave_name ? '%postname%' : $asset->post_name;
		$route_prefix = $this->route_prefix( $asset );

		if ( '' === $slug || null === $route_prefix ) {
			return $post_link;
		}

		return home_url( user_trailingslashit( $route_prefix . '/' . $slug ) );
	}

	private function route_prefix( \WP_Post $asset ): ?string {
		if ( has_term( self::FOREX_TYPE_SLUG, self::TYPE_TAXONOMY, $asset ) ) {
			return 'forex';
		}

		if ( has_term( self::STOCK_TYPE_SLUG, self::TYPE_TAXONOMY, $asset )
			&& has_term( self::TADAWUL_EXCHANGE_SLUG, self::EXCHANGE_TAXONOMY, $asset )
		) {
			return 'aswaq/tadawul';
		}

		return null;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function forex_tax_query(): array {
		return array(
			array(
				'taxonomy' => self::TYPE_TAXONOMY,
				'field'    => 'slug',
				'terms'    => array( self::FOREX_TYPE_SLUG ),
			),
		);
	}

	/**
	 * @return array<string|int, array<string, mixed>|string>
	 */
	private function tadawul_tax_query(): array {
		return array(
			'relation' => 'AND',
			array(
				'taxonomy' => self::TYPE_TAXONOMY,
				'field'    => 'slug',
				'terms'    => array( self::STOCK_TYPE_SLUG ),
			),
			array(
				'taxonomy' => self::EXCHANGE_TAXONOMY,
				'field'    => 'slug',
				'terms'    => array( self::TADAWUL_EXCHANGE_SLUG ),
			),
		);
	}

	/**
	 * Taxonomy archives stay disabled until their public URL contracts are approved.
	 *
	 * @param array<string, string> $labels Taxonomy labels.
	 * @return array<string, mixed>
	 */
	private function taxonomy_arguments( array $labels ): array {
		return array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_rest'       => true,
			'show_admin_column'  => true,
			'show_in_nav_menus'  => false,
			'hierarchical'       => true,
			'query_var'          => false,
			'rewrite'            => false,
		);
	}

	/**
	 * @return array<string, string>
	 */
	private function post_type_labels(): array {
		return array(
			'name'                     => __( 'Assets', 'alborsa-platform' ),
			'singular_name'            => __( 'Asset', 'alborsa-platform' ),
			'add_new'                  => __( 'Add New Asset', 'alborsa-platform' ),
			'add_new_item'             => __( 'Add New Asset', 'alborsa-platform' ),
			'edit_item'                => __( 'Edit Asset', 'alborsa-platform' ),
			'new_item'                 => __( 'New Asset', 'alborsa-platform' ),
			'view_item'                => __( 'View Asset', 'alborsa-platform' ),
			'view_items'               => __( 'View Assets', 'alborsa-platform' ),
			'search_items'             => __( 'Search Assets', 'alborsa-platform' ),
			'not_found'                => __( 'No assets found.', 'alborsa-platform' ),
			'not_found_in_trash'       => __( 'No assets found in Trash.', 'alborsa-platform' ),
			'all_items'                => __( 'All Assets', 'alborsa-platform' ),
			'archives'                 => __( 'Asset Archives', 'alborsa-platform' ),
			'attributes'               => __( 'Asset Attributes', 'alborsa-platform' ),
			'insert_into_item'         => __( 'Insert into asset', 'alborsa-platform' ),
			'uploaded_to_this_item'    => __( 'Uploaded to this asset', 'alborsa-platform' ),
			'featured_image'           => __( 'Featured image', 'alborsa-platform' ),
			'set_featured_image'       => __( 'Set featured image', 'alborsa-platform' ),
			'remove_featured_image'    => __( 'Remove featured image', 'alborsa-platform' ),
			'use_featured_image'       => __( 'Use as featured image', 'alborsa-platform' ),
			'menu_name'                => __( 'Assets', 'alborsa-platform' ),
			'filter_items_list'        => __( 'Filter assets list', 'alborsa-platform' ),
			'filter_by_date'           => __( 'Filter assets by date', 'alborsa-platform' ),
			'items_list_navigation'    => __( 'Assets list navigation', 'alborsa-platform' ),
			'items_list'               => __( 'Assets list', 'alborsa-platform' ),
			'item_published'           => __( 'Asset published.', 'alborsa-platform' ),
			'item_published_privately' => __( 'Asset published privately.', 'alborsa-platform' ),
			'item_reverted_to_draft'   => __( 'Asset reverted to draft.', 'alborsa-platform' ),
			'item_trashed'             => __( 'Asset moved to Trash.', 'alborsa-platform' ),
			'item_scheduled'           => __( 'Asset scheduled.', 'alborsa-platform' ),
			'item_updated'             => __( 'Asset updated.', 'alborsa-platform' ),
			'item_link'                => __( 'Asset Link', 'alborsa-platform' ),
			'item_link_description'    => __( 'A link to an asset.', 'alborsa-platform' ),
		);
	}

	/**
	 * @return array<string, string>
	 */
	private function asset_type_labels(): array {
		return array(
			'name'          => __( 'Asset Types', 'alborsa-platform' ),
			'singular_name' => __( 'Asset Type', 'alborsa-platform' ),
			'search_items'  => __( 'Search Asset Types', 'alborsa-platform' ),
			'all_items'     => __( 'All Asset Types', 'alborsa-platform' ),
			'edit_item'     => __( 'Edit Asset Type', 'alborsa-platform' ),
			'update_item'   => __( 'Update Asset Type', 'alborsa-platform' ),
			'add_new_item'  => __( 'Add New Asset Type', 'alborsa-platform' ),
			'new_item_name' => __( 'New Asset Type Name', 'alborsa-platform' ),
			'menu_name'     => __( 'Asset Types', 'alborsa-platform' ),
		);
	}

	/**
	 * @return array<string, string>
	 */
	private function exchange_labels(): array {
		return array(
			'name'          => __( 'Exchanges', 'alborsa-platform' ),
			'singular_name' => __( 'Exchange', 'alborsa-platform' ),
			'search_items'  => __( 'Search Exchanges', 'alborsa-platform' ),
			'all_items'     => __( 'All Exchanges', 'alborsa-platform' ),
			'edit_item'     => __( 'Edit Exchange', 'alborsa-platform' ),
			'update_item'   => __( 'Update Exchange', 'alborsa-platform' ),
			'add_new_item'  => __( 'Add New Exchange', 'alborsa-platform' ),
			'new_item_name' => __( 'New Exchange Name', 'alborsa-platform' ),
			'menu_name'     => __( 'Exchanges', 'alborsa-platform' ),
		);
	}

	/**
	 * @return array<string, string>
	 */
	private function sector_labels(): array {
		return array(
			'name'          => __( 'Sectors', 'alborsa-platform' ),
			'singular_name' => __( 'Sector', 'alborsa-platform' ),
			'search_items'  => __( 'Search Sectors', 'alborsa-platform' ),
			'all_items'     => __( 'All Sectors', 'alborsa-platform' ),
			'edit_item'     => __( 'Edit Sector', 'alborsa-platform' ),
			'update_item'   => __( 'Update Sector', 'alborsa-platform' ),
			'add_new_item'  => __( 'Add New Sector', 'alborsa-platform' ),
			'new_item_name' => __( 'New Sector Name', 'alborsa-platform' ),
			'menu_name'     => __( 'Sectors', 'alborsa-platform' ),
		);
	}
}

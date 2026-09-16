<?php
/**
 * Broker profiles, taxonomies, reviews, and public read contracts.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\Brokers;

use Alborsa\Platform\Foundation\Module;

defined( 'ABSPATH' ) || exit;

final class BrokersModule implements Module {
	public const POST_TYPE             = 'alb_broker';
	public const TYPE_TAXONOMY         = 'alb_broker_type';
	public const JURISDICTION_TAXONOMY = 'alb_jurisdiction';

	public function register(): void {
		add_action( 'init', array( $this, 'initialize' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	public function initialize(): void {
		$this->register_post_type();
		$this->register_taxonomies();
		( new BrokerMeta() )->register();
		( new BrokerReviewMeta() )->register();
		( new BrokerAdminMetaBox( new BrokerMeta() ) )->register();
	}

	public function register_rest_routes(): void {
		global $wpdb;

		if ( ! $wpdb instanceof \wpdb ) {
			return;
		}

		$reviews = new BrokerReviewRepository( $wpdb );
		( new BrokerRestController( new BrokerPresenter( new BrokerMeta(), $reviews ) ) )->register_routes();
	}

	public function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => $this->post_type_labels(),
				'public'          => true,
				'has_archive'     => 'brokers',
				'rewrite'         => array(
					'slug'       => 'brokers',
					'with_front' => false,
				),
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
			$this->taxonomy_arguments( $this->broker_type_labels() )
		);
		register_taxonomy(
			self::JURISDICTION_TAXONOMY,
			array( self::POST_TYPE ),
			$this->taxonomy_arguments( $this->jurisdiction_labels() )
		);
	}

	/**
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
			'hierarchical'       => true,
			'query_var'          => false,
			'rewrite'            => false,
		);
	}

	/** @return array<string, string> */
	private function post_type_labels(): array {
		return array(
			'name'               => __( 'Brokers', 'alborsa-platform' ),
			'singular_name'      => __( 'Broker', 'alborsa-platform' ),
			'add_new'            => __( 'Add New Broker', 'alborsa-platform' ),
			'add_new_item'       => __( 'Add New Broker', 'alborsa-platform' ),
			'edit_item'          => __( 'Edit Broker', 'alborsa-platform' ),
			'new_item'           => __( 'New Broker', 'alborsa-platform' ),
			'view_item'          => __( 'View Broker', 'alborsa-platform' ),
			'search_items'       => __( 'Search Brokers', 'alborsa-platform' ),
			'not_found'          => __( 'No brokers found.', 'alborsa-platform' ),
			'not_found_in_trash' => __( 'No brokers found in Trash.', 'alborsa-platform' ),
			'all_items'          => __( 'All Brokers', 'alborsa-platform' ),
			'archives'           => __( 'Broker Archives', 'alborsa-platform' ),
			'menu_name'          => __( 'Brokers', 'alborsa-platform' ),
			'item_updated'       => __( 'Broker updated.', 'alborsa-platform' ),
			'item_published'     => __( 'Broker published.', 'alborsa-platform' ),
		);
	}

	/** @return array<string, string> */
	private function broker_type_labels(): array {
		return array(
			'name'          => __( 'Broker Types', 'alborsa-platform' ),
			'singular_name' => __( 'Broker Type', 'alborsa-platform' ),
			'search_items'  => __( 'Search Broker Types', 'alborsa-platform' ),
			'all_items'     => __( 'All Broker Types', 'alborsa-platform' ),
			'edit_item'     => __( 'Edit Broker Type', 'alborsa-platform' ),
			'update_item'   => __( 'Update Broker Type', 'alborsa-platform' ),
			'add_new_item'  => __( 'Add New Broker Type', 'alborsa-platform' ),
			'new_item_name' => __( 'New Broker Type Name', 'alborsa-platform' ),
			'menu_name'     => __( 'Broker Types', 'alborsa-platform' ),
		);
	}

	/** @return array<string, string> */
	private function jurisdiction_labels(): array {
		return array(
			'name'          => __( 'Broker Jurisdictions', 'alborsa-platform' ),
			'singular_name' => __( 'Broker Jurisdiction', 'alborsa-platform' ),
			'search_items'  => __( 'Search Jurisdictions', 'alborsa-platform' ),
			'all_items'     => __( 'All Jurisdictions', 'alborsa-platform' ),
			'edit_item'     => __( 'Edit Jurisdiction', 'alborsa-platform' ),
			'update_item'   => __( 'Update Jurisdiction', 'alborsa-platform' ),
			'add_new_item'  => __( 'Add New Jurisdiction', 'alborsa-platform' ),
			'new_item_name' => __( 'New Jurisdiction Name', 'alborsa-platform' ),
			'menu_name'     => __( 'Jurisdictions', 'alborsa-platform' ),
		);
	}
}

<?php
/**
 * Editorial content type registration.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\Editorial;

use Alborsa\Platform\Foundation\Module;

defined( 'ABSPATH' ) || exit;

final class EditorialModule implements Module {
	public const ANALYSIS_POST_TYPE = 'alb_analysis';
	public const GUIDE_POST_TYPE    = 'alb_guide';

	public function register(): void {
		add_action( 'init', array( $this, 'initialize' ) );
	}

	public function initialize(): void {
		$this->register_post_types();
	}

	public function register_post_types(): void {
		register_post_type(
			self::ANALYSIS_POST_TYPE,
			$this->post_type_arguments( 'tahleel', $this->analysis_labels() )
		);

		register_post_type(
			self::GUIDE_POST_TYPE,
			$this->post_type_arguments( 'taleem', $this->guide_labels() )
		);
	}

	/**
	 * @param array<string, string> $labels Post type labels.
	 * @return array<string, mixed>
	 */
	private function post_type_arguments( string $rewrite_slug, array $labels ): array {
		return array(
			'labels'          => $labels,
			'public'          => true,
			'has_archive'     => true,
			'rewrite'         => array(
				'slug'       => $rewrite_slug,
				'with_front' => false,
			),
			'show_ui'         => true,
			'show_in_rest'    => true,
			'supports'        => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'revisions' ),
			'hierarchical'    => false,
			'capability_type' => 'post',
			'map_meta_cap'    => true,
		);
	}

	/**
	 * @return array<string, string>
	 */
	private function analysis_labels(): array {
		return array(
			'name'                     => __( 'Analyses', 'alborsa-platform' ),
			'singular_name'            => __( 'Analysis', 'alborsa-platform' ),
			'add_new'                  => __( 'Add New Analysis', 'alborsa-platform' ),
			'add_new_item'             => __( 'Add New Analysis', 'alborsa-platform' ),
			'edit_item'                => __( 'Edit Analysis', 'alborsa-platform' ),
			'new_item'                 => __( 'New Analysis', 'alborsa-platform' ),
			'view_item'                => __( 'View Analysis', 'alborsa-platform' ),
			'view_items'               => __( 'View Analyses', 'alborsa-platform' ),
			'search_items'             => __( 'Search Analyses', 'alborsa-platform' ),
			'not_found'                => __( 'No analyses found.', 'alborsa-platform' ),
			'not_found_in_trash'       => __( 'No analyses found in Trash.', 'alborsa-platform' ),
			'parent_item_colon'        => __( 'Parent Analysis:', 'alborsa-platform' ),
			'all_items'                => __( 'All Analyses', 'alborsa-platform' ),
			'archives'                 => __( 'Analysis Archives', 'alborsa-platform' ),
			'attributes'               => __( 'Analysis Attributes', 'alborsa-platform' ),
			'insert_into_item'         => __( 'Insert into analysis', 'alborsa-platform' ),
			'uploaded_to_this_item'    => __( 'Uploaded to this analysis', 'alborsa-platform' ),
			'featured_image'           => __( 'Featured image', 'alborsa-platform' ),
			'set_featured_image'       => __( 'Set featured image', 'alborsa-platform' ),
			'remove_featured_image'    => __( 'Remove featured image', 'alborsa-platform' ),
			'use_featured_image'       => __( 'Use as featured image', 'alborsa-platform' ),
			'menu_name'                => __( 'Analyses', 'alborsa-platform' ),
			'filter_items_list'        => __( 'Filter analyses list', 'alborsa-platform' ),
			'filter_by_date'           => __( 'Filter analyses by date', 'alborsa-platform' ),
			'items_list_navigation'    => __( 'Analyses list navigation', 'alborsa-platform' ),
			'items_list'               => __( 'Analyses list', 'alborsa-platform' ),
			'item_published'           => __( 'Analysis published.', 'alborsa-platform' ),
			'item_published_privately' => __( 'Analysis published privately.', 'alborsa-platform' ),
			'item_reverted_to_draft'   => __( 'Analysis reverted to draft.', 'alborsa-platform' ),
			'item_trashed'             => __( 'Analysis moved to Trash.', 'alborsa-platform' ),
			'item_scheduled'           => __( 'Analysis scheduled.', 'alborsa-platform' ),
			'item_updated'             => __( 'Analysis updated.', 'alborsa-platform' ),
			'item_link'                => __( 'Analysis Link', 'alborsa-platform' ),
			'item_link_description'    => __( 'A link to an analysis.', 'alborsa-platform' ),
		);
	}

	/**
	 * @return array<string, string>
	 */
	private function guide_labels(): array {
		return array(
			'name'                     => __( 'Guides', 'alborsa-platform' ),
			'singular_name'            => __( 'Guide', 'alborsa-platform' ),
			'add_new'                  => __( 'Add New Guide', 'alborsa-platform' ),
			'add_new_item'             => __( 'Add New Guide', 'alborsa-platform' ),
			'edit_item'                => __( 'Edit Guide', 'alborsa-platform' ),
			'new_item'                 => __( 'New Guide', 'alborsa-platform' ),
			'view_item'                => __( 'View Guide', 'alborsa-platform' ),
			'view_items'               => __( 'View Guides', 'alborsa-platform' ),
			'search_items'             => __( 'Search Guides', 'alborsa-platform' ),
			'not_found'                => __( 'No guides found.', 'alborsa-platform' ),
			'not_found_in_trash'       => __( 'No guides found in Trash.', 'alborsa-platform' ),
			'parent_item_colon'        => __( 'Parent Guide:', 'alborsa-platform' ),
			'all_items'                => __( 'All Guides', 'alborsa-platform' ),
			'archives'                 => __( 'Guide Archives', 'alborsa-platform' ),
			'attributes'               => __( 'Guide Attributes', 'alborsa-platform' ),
			'insert_into_item'         => __( 'Insert into guide', 'alborsa-platform' ),
			'uploaded_to_this_item'    => __( 'Uploaded to this guide', 'alborsa-platform' ),
			'featured_image'           => __( 'Featured image', 'alborsa-platform' ),
			'set_featured_image'       => __( 'Set featured image', 'alborsa-platform' ),
			'remove_featured_image'    => __( 'Remove featured image', 'alborsa-platform' ),
			'use_featured_image'       => __( 'Use as featured image', 'alborsa-platform' ),
			'menu_name'                => __( 'Guides', 'alborsa-platform' ),
			'filter_items_list'        => __( 'Filter guides list', 'alborsa-platform' ),
			'filter_by_date'           => __( 'Filter guides by date', 'alborsa-platform' ),
			'items_list_navigation'    => __( 'Guides list navigation', 'alborsa-platform' ),
			'items_list'               => __( 'Guides list', 'alborsa-platform' ),
			'item_published'           => __( 'Guide published.', 'alborsa-platform' ),
			'item_published_privately' => __( 'Guide published privately.', 'alborsa-platform' ),
			'item_reverted_to_draft'   => __( 'Guide reverted to draft.', 'alborsa-platform' ),
			'item_trashed'             => __( 'Guide moved to Trash.', 'alborsa-platform' ),
			'item_scheduled'           => __( 'Guide scheduled.', 'alborsa-platform' ),
			'item_updated'             => __( 'Guide updated.', 'alborsa-platform' ),
			'item_link'                => __( 'Guide Link', 'alborsa-platform' ),
			'item_link_description'    => __( 'A link to a guide.', 'alborsa-platform' ),
		);
	}
}

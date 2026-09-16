<?php
/**
 * Public asset REST representations.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\Assets;

use Alborsa\Platform\Shared\Relationships\ContentAssetRelationshipService;
use WP_Error;

defined( 'ABSPATH' ) || exit;

final class AssetPresenter {
	private ContentAssetRelationshipService $relationships;

	public function __construct( ContentAssetRelationshipService $relationships ) {
		$this->relationships = $relationships;
	}

	/**
	 * @return array<string, mixed>|WP_Error
	 */
	public function summary( \WP_Post $asset ): array|WP_Error {
		$asset_types = $this->term_slugs( $asset->ID, AssetsModule::TYPE_TAXONOMY );
		$exchanges   = $this->term_slugs( $asset->ID, AssetsModule::EXCHANGE_TAXONOMY );
		$sectors     = $this->term_slugs( $asset->ID, AssetsModule::SECTOR_TAXONOMY );

		foreach ( array( $asset_types, $exchanges, $sectors ) as $terms ) {
			if ( $terms instanceof WP_Error ) {
				return $terms;
			}
		}

		return array(
			'id'               => $asset->ID,
			'slug'             => $asset->post_name,
			'title'            => wp_strip_all_tags( (string) get_the_title( $asset ) ),
			'excerpt'          => wp_strip_all_tags( (string) get_the_excerpt( $asset ) ),
			'url'              => $this->permalink( $asset ),
			'canonical_symbol' => $this->text_meta( $asset, AssetMeta::CANONICAL_SYMBOL ),
			'base_currency'    => $this->text_meta( $asset, AssetMeta::BASE_CURRENCY ),
			'quote_currency'   => $this->text_meta( $asset, AssetMeta::QUOTE_CURRENCY ),
			'isin'             => $this->text_meta( $asset, AssetMeta::ISIN ),
			'official_website' => esc_url_raw( $this->text_meta( $asset, AssetMeta::OFFICIAL_WEBSITE ) ),
			'listing_status'   => sanitize_key( $this->text_meta( $asset, AssetMeta::LISTING_STATUS ) ),
			'asset_types'      => $asset_types,
			'exchanges'        => $exchanges,
			'sectors'          => $sectors,
		);
	}

	/**
	 * @return array<string, mixed>|WP_Error
	 */
	public function detail( \WP_Post $asset ): array|WP_Error {
		$summary = $this->summary( $asset );

		if ( $summary instanceof WP_Error ) {
			return $summary;
		}

		$related_content = $this->related_content( $asset );

		if ( $related_content instanceof WP_Error ) {
			return $related_content;
		}

		$summary['content']         = wp_kses_post( $asset->post_content );
		$summary['related_content'] = $related_content;

		return $summary;
	}

	/**
	 * @return list<string>|WP_Error
	 */
	private function term_slugs( int $post_id, string $taxonomy ): array|WP_Error {
		$terms = get_the_terms( $post_id, $taxonomy );

		if ( $terms instanceof WP_Error ) {
			return new WP_Error(
				'alborsa_platform_asset_terms_unavailable',
				__( 'Asset classifications are temporarily unavailable.', 'alborsa-platform' ),
				array( 'status' => 503 )
			);
		}

		if ( false === $terms ) {
			return array();
		}

		return array_values(
			array_map(
				static fn( \WP_Term $term ): string => $term->slug,
				$terms
			)
		);
	}

	private function text_meta( \WP_Post $asset, string $meta_key ): string {
		$meta_value = get_post_meta( $asset->ID, $meta_key, true );

		return is_scalar( $meta_value ) ? sanitize_text_field( (string) $meta_value ) : '';
	}

	private function permalink( \WP_Post $post ): string {
		return esc_url_raw( get_permalink( $post ) );
	}

	/**
	 * @return list<array<string, int|string>>|WP_Error
	 */
	private function related_content( \WP_Post $asset ): array|WP_Error {
		$relationships = $this->relationships->for_asset( $asset );

		if ( $relationships instanceof WP_Error ) {
			return new WP_Error(
				'alborsa_platform_asset_relationships_unavailable',
				__( 'Related content is temporarily unavailable.', 'alborsa-platform' ),
				array( 'status' => 503 )
			);
		}

		$related_content = array();
		$content_by_id   = $this->related_content_posts( $relationships );

		foreach ( $relationships as $relationship ) {
			$related_entry = $this->related_content_entry( $relationship, $content_by_id );

			if ( null !== $related_entry ) {
				$related_content[] = $related_entry;
			}
		}

		return $related_content;
	}

	/**
	 * @param array<string, mixed> $relationship Stored relationship row.
	 * @param array<int, \WP_Post> $content_by_id Published content indexed by ID.
	 * @return array<string, int|string>|null
	 */
	private function related_content_entry( array $relationship, array $content_by_id ): ?array {
		$content_id = (int) ( $relationship['from_object_id'] ?? 0 );
		$content    = $content_by_id[ $content_id ] ?? null;

		if ( ! $content instanceof \WP_Post ) {
			return null;
		}

		if ( ! $this->relationships->supports_content( $content ) ) {
			return null;
		}

		$relation_type = sanitize_key( (string) ( $relationship['relation_type'] ?? '' ) );

		if ( ! $this->is_public_relation( $relation_type ) ) {
			return null;
		}

		return array(
			'id'            => $content->ID,
			'type'          => $content->post_type,
			'relation_type' => $relation_type,
			'title'         => wp_strip_all_tags( (string) get_the_title( $content ) ),
			'url'           => $this->permalink( $content ),
		);
	}

	/**
	 * @param list<array<string, mixed>> $relationships Stored relationship rows.
	 * @return array<int, \WP_Post>
	 */
	private function related_content_posts( array $relationships ): array {
		$content_ids = array_values(
			array_unique(
				array_map(
					static fn( array $relationship ): int => (int) ( $relationship['from_object_id'] ?? 0 ),
					$relationships
				)
			)
		);
		$content_ids = array_values( array_filter( $content_ids ) );

		if ( array() === $content_ids ) {
			return array();
		}

		$posts         = get_posts(
			array(
				'post_type'           => ContentAssetRelationshipService::CONTENT_TYPES,
				'post_status'         => 'publish',
				'post__in'            => $content_ids,
				'posts_per_page'      => count( $content_ids ),
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
				'suppress_filters'    => false,
			)
		);
		$content_by_id = array();

		foreach ( $posts as $post ) {
			$content_by_id[ $post->ID ] = $post;
		}

		return $content_by_id;
	}

	private function is_public_relation( string $relation_type ): bool {
		return in_array(
			$relation_type,
			array( ContentAssetRelationshipService::MENTIONS, ContentAssetRelationshipService::ANALYZES ),
			true
		);
	}
}

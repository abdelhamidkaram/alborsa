<?php
/**
 * Read-only presentation helpers for section and singular templates.
 *
 * @package Alborsa
 */

declare(strict_types=1);

namespace Alborsa\Theme\Content;

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the Arabic presentation label for a public content type.
 *
 * @param string $post_type Registered WordPress post type.
 */
function post_type_label( string $post_type ): string {
	return match ( $post_type ) {
		'post'         => __( 'خبر', 'alborsa' ),
		'alb_analysis' => __( 'تحليل', 'alborsa' ),
		'alb_guide'    => __( 'دليل تعليمي', 'alborsa' ),
		'alb_asset'    => __( 'أصل مالي', 'alborsa' ),
		default        => __( 'محتوى', 'alborsa' ),
	};
}

/**
 * Return a comma-separated, escaped-later term label string.
 *
 * @param int    $post_id  WordPress post ID.
 * @param string $taxonomy Registered taxonomy name.
 */
function term_names( int $post_id, string $taxonomy ): string {
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return '';
	}

	$terms = get_the_terms( $post_id, $taxonomy );

	if ( false === $terms || is_wp_error( $terms ) ) {
		return '';
	}

	return implode(
		'، ',
		array_map(
			static fn( \WP_Term $term ): string => $term->name,
			$terms
		)
	);
}

/**
 * Return published asset records for the markets presentation layer.
 *
 * Prices are intentionally absent until the plugin exposes an approved quote
 * read model.
 *
 * @return list<array{
 *   id: int,
 *   title: string,
 *   symbol: string,
 *   url: string,
 *   types: list<string>,
 *   exchanges: list<string>,
 *   type_labels: string,
 *   exchange_labels: string,
 *   quote_currency: string
 * }>
 */
function published_assets(): array {
	if ( ! post_type_exists( 'alb_asset' ) ) {
		return array();
	}

	$asset_query = new \WP_Query(
		array(
			'post_type'              => 'alb_asset',
			'post_status'            => 'publish',
			'posts_per_page'         => 60,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'suppress_filters'       => false,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => true,
		)
	);

	$records = array();

	foreach ( $asset_query->posts as $asset_post ) {
		if ( ! $asset_post instanceof \WP_Post ) {
			continue;
		}

		$asset_url = get_permalink( $asset_post );

		if ( ! is_string( $asset_url ) ) {
			continue;
		}

		$type_terms     = get_the_terms( $asset_post, 'alb_asset_type' );
		$exchange_terms = get_the_terms( $asset_post, 'alb_exchange' );
		$type_terms     = is_array( $type_terms ) ? $type_terms : array();
		$exchange_terms = is_array( $exchange_terms ) ? $exchange_terms : array();

		$records[] = array(
			'id'              => $asset_post->ID,
			'title'           => get_the_title( $asset_post ),
			'symbol'          => (string) get_post_meta( $asset_post->ID, 'alb_canonical_symbol', true ),
			'url'             => $asset_url,
			'types'           => array_values( wp_list_pluck( $type_terms, 'slug' ) ),
			'exchanges'       => array_values( wp_list_pluck( $exchange_terms, 'slug' ) ),
			'type_labels'     => implode( '، ', wp_list_pluck( $type_terms, 'name' ) ),
			'exchange_labels' => implode( '، ', wp_list_pluck( $exchange_terms, 'name' ) ),
			'quote_currency'  => (string) get_post_meta( $asset_post->ID, 'alb_quote_currency', true ),
		);
	}

	return $records;
}

/**
 * Count records that contain at least one matching taxonomy slug.
 *
 * @param list<array{types: list<string>, exchanges: list<string>}> $asset_records Asset read models.
 * @param array                                                     $slugs         Taxonomy slugs to match.
 * @param 'types'|'exchanges'                                       $field         Record field to inspect.
 */
function count_assets( array $asset_records, array $slugs, string $field ): int {
	$count = 0;

	foreach ( $asset_records as $asset_record ) {
		if ( array_intersect( $slugs, $asset_record[ $field ] ) ) {
			++$count;
		}
	}

	return $count;
}

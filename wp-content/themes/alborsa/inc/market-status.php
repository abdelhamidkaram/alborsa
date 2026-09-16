<?php
/**
 * Read models for the global market-status ribbon.
 *
 * @package Alborsa
 */

declare(strict_types=1);

namespace Alborsa\Theme\MarketStatus;

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return a bounded list of published assets without claiming quote availability.
 *
 * @return list<array{title: string, symbol: string, url: string}>
 */
function assets(): array {
	if ( ! post_type_exists( 'alb_asset' ) ) {
		return array();
	}

	$asset_query = new \WP_Query(
		array(
			'post_type'              => 'alb_asset',
			'post_status'            => 'publish',
			'posts_per_page'         => 6,
			'orderby'                => 'menu_order title',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'suppress_filters'       => false,
			'update_post_term_cache' => false,
		)
	);

	$market_assets = array();

	foreach ( $asset_query->posts as $asset ) {
		$asset_url = get_permalink( $asset );

		if ( ! is_string( $asset_url ) ) {
			continue;
		}

		$market_assets[] = array(
			'title'  => get_the_title( $asset ),
			'symbol' => (string) get_post_meta( $asset->ID, 'alb_canonical_symbol', true ),
			'url'    => $asset_url,
		);
	}

	return $market_assets;
}

<?php
/**
 * Bounded content queries for the homepage templates.
 *
 * @package Alborsa
 */

declare(strict_types=1);

namespace Alborsa\Theme\HomeContent;

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the newest published entries for an available post type.
 *
 * @param string $post_type      Registered WordPress post type.
 * @param int    $posts_per_page Maximum entries to return.
 * @return list<\WP_Post>
 */
function published_posts( string $post_type, int $posts_per_page ): array {
	if ( ! post_type_exists( $post_type ) ) {
		return array();
	}

	$content_query = new \WP_Query(
		array(
			'post_type'              => $post_type,
			'post_status'            => 'publish',
			'posts_per_page'         => $posts_per_page,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'suppress_filters'       => false,
			'update_post_term_cache' => true,
		)
	);

	return array_values( $content_query->posts );
}

/**
 * Return a plain-text summary suitable for compact homepage modules.
 *
 * @param \WP_Post $content_post Published content entry.
 */
function post_summary( \WP_Post $content_post ): string {
	$summary = get_the_excerpt( $content_post );

	if ( '' === trim( $summary ) ) {
		$summary = wp_trim_words( wp_strip_all_tags( $content_post->post_content ), 28 );
	}

	return wp_strip_all_tags( $summary );
}

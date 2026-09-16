<?php
/**
 * Broker directory page fallback when the broker archive is unavailable.
 *
 * @package Alborsa
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$broker_query = new WP_Query(
	array(
		'post_type'              => 'alb_broker',
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

get_header();
get_template_part(
	'template-parts/content/broker-directory',
	null,
	array( 'brokers' => array_values( $broker_query->posts ) )
);
get_footer();

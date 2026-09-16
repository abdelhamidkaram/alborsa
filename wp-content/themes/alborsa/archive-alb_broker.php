<?php
// phpcs:ignoreFile WordPress.Files.FileName.NotHyphenatedLowercase -- WordPress requires the registered post-type name in this template filename.
/**
 * Published broker archive.
 *
 * @package Alborsa
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wp_query;

$brokers = $wp_query instanceof WP_Query ? array_values( $wp_query->posts ) : array();

get_header();
get_template_part(
	'template-parts/content/broker-directory',
	null,
	array( 'brokers' => $brokers )
);

if ( $wp_query instanceof WP_Query && $wp_query->max_num_pages > 1 ) {
	echo '<div class="site-container broker-pagination">';
	the_posts_pagination(
		array(
			'mid_size'  => 1,
			'prev_text' => esc_html__( 'السابق', 'alborsa' ),
			'next_text' => esc_html__( 'التالي', 'alborsa' ),
		)
	);
	echo '</div>';
}

get_footer();

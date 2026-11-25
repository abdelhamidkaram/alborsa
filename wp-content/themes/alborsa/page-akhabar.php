<?php
/**
 * News page fallback when /akhabar/ is configured as a regular page.
 *
 * @package Alborsa
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
get_template_part( 'template-parts/content/news-archive' );
get_footer();

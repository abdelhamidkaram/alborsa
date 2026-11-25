<?php
/**
 * Singular editorial content template.
 *
 * @package Alborsa
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( is_singular( 'alb_asset' ) ) {
	get_template_part( 'template-parts/content/asset-profile' );
	return;
}

get_header();

if ( is_singular( 'post' ) ) {
	get_template_part( 'template-parts/content/news-article' );
} else {
	get_template_part( 'template-parts/content/editorial-single' );
}

get_footer();

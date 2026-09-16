<?php
/**
 * Constants declared by the plugin bootstrap for static analysis.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

const ALBORSA_PLATFORM_FILE    = '/wp-content/plugins/alborsa-platform/alborsa-platform.php';
const ALBORSA_PLATFORM_PATH    = '/wp-content/plugins/alborsa-platform/';

/**
 * Polylang public function stubs used only during static analysis.
 *
 * @param array<string, mixed> $arguments Query arguments.
 * @return list<string>
 */
function pll_languages_list( array $arguments = array() ): array {
	unset( $arguments );

	return array();
}

function pll_get_term_language( int $term_id ): string|false {
	unset( $term_id );

	return false;
}

function pll_set_post_language( int $post_id, string $language ): void {
	unset( $post_id, $language );
}

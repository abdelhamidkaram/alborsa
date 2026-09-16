<?php
/**
 * Polylang language boundary for editorial imports.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\Editorial;

defined( 'ABSPATH' ) || exit;

final class PolylangEditorialLanguageGateway implements EditorialLanguageGateway {
	public function available(): bool {
		return function_exists( 'pll_languages_list' ) && function_exists( 'pll_set_post_language' );
	}

	public function has_language( string $language ): bool {
		return $this->available() && in_array( $language, pll_languages_list( array( 'fields' => 'slug' ) ), true );
	}

	public function set_post_language( int $post_id, string $language ): void {
		if ( $this->available() ) {
			pll_set_post_language( $post_id, $language );
		}
	}
}

<?php
/**
 * Polylang implementation of the asset language boundary.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\Assets;

defined( 'ABSPATH' ) || exit;

final class PolylangAssetLanguageGateway implements AssetLanguageGateway {
	public function available(): bool {
		return function_exists( 'pll_languages_list' )
			&& function_exists( 'pll_set_post_language' )
			&& function_exists( 'pll_get_term_language' );
	}

	public function has_language( string $language ): bool {
		return $this->available()
			&& in_array( $language, pll_languages_list( array( 'fields' => 'slug' ) ), true );
	}

	public function term_language( int $term_id ): string|false {
		return $this->available() ? pll_get_term_language( $term_id ) : false;
	}

	public function set_post_language( int $post_id, string $language ): void {
		if ( $this->available() ) {
			pll_set_post_language( $post_id, $language );
		}
	}
}

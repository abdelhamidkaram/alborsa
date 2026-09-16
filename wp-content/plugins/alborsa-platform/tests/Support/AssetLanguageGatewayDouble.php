<?php
/**
 * Configurable language boundary test double.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Support;

use Alborsa\Platform\Modules\Assets\AssetLanguageGateway;

final class AssetLanguageGatewayDouble implements AssetLanguageGateway {
	public bool $is_available = true;

	/** @var list<string> */
	public array $languages = array( 'ar', 'en' );

	public string|false $language_for_terms = 'ar';

	/** @var list<array{post_id: int, language: string}> */
	public array $assigned_posts = array();

	public function available(): bool {
		return $this->is_available;
	}

	public function has_language( string $language ): bool {
		return in_array( $language, $this->languages, true );
	}

	public function term_language( int $term_id ): string|false {
		unset( $term_id );

		return $this->language_for_terms;
	}

	public function set_post_language( int $post_id, string $language ): void {
		$this->assigned_posts[] = array(
			'post_id'  => $post_id,
			'language' => $language,
		);
	}
}

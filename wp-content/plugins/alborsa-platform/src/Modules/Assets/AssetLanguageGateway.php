<?php
/**
 * Language boundary used by asset write services.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\Assets;

defined( 'ABSPATH' ) || exit;

interface AssetLanguageGateway {
	public function available(): bool;

	public function has_language( string $language ): bool;

	public function term_language( int $term_id ): string|false;

	public function set_post_language( int $post_id, string $language ): void;
}

<?php
/**
 * Language boundary for background editorial imports.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\Editorial;

defined( 'ABSPATH' ) || exit;

interface EditorialLanguageGateway {
	public function available(): bool;

	public function has_language( string $language ): bool;

	public function set_post_language( int $post_id, string $language ): void;
}

<?php
/**
 * Feed reader boundary for deterministic import tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\Editorial;

use WP_Error;

defined( 'ABSPATH' ) || exit;

interface RssFeedReader {
	/**
	 * @return list<array{title: string, url: string, content: string}>|WP_Error
	 */
	public function read( string $url, int $limit ): array|WP_Error;
}

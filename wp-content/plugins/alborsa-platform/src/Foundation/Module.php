<?php
/**
 * Platform module contract.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Foundation;

defined( 'ABSPATH' ) || exit;

interface Module {
	public function register(): void;
}

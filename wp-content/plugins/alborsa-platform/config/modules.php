<?php
/**
 * Registered platform modules, in boot order.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use Alborsa\Platform\Foundation\FoundationModule;

return array(
	new FoundationModule(),
);

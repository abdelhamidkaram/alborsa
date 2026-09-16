<?php
/**
 * Capability assignments installed by Foundation migrations.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use Alborsa\Platform\Foundation\RoleCapabilities;

return array(
	'administrator' => array(
		RoleCapabilities::MANAGE_PLATFORM,
	),
);

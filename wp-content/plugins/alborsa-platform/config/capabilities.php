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
	'administrator' => array_merge(
		array( RoleCapabilities::MANAGE_PLATFORM ),
		RoleCapabilities::CONTENT_EDITOR_CAPABILITIES
	),
	'editor'        => RoleCapabilities::CONTENT_EDITOR_CAPABILITIES,
);

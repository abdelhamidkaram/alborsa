<?php
/**
 * Main plugin composition root.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform;

use Alborsa\Platform\Foundation\ModuleRegistry;

defined( 'ABSPATH' ) || exit;

final class Plugin {
	private ModuleRegistry $module_registry;

	private function __construct( ModuleRegistry $module_registry ) {
		$this->module_registry = $module_registry;
	}

	public static function create(): self {
		/** @var list<\Alborsa\Platform\Foundation\Module> $modules */
		$modules = require dirname( __DIR__ ) . '/config/modules.php';

		return new self( new ModuleRegistry( $modules ) );
	}

	public function register(): void {
		$this->module_registry->register();
	}
}

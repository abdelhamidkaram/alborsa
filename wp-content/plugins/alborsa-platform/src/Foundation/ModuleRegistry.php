<?php
/**
 * Ordered platform module registry.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Foundation;

defined( 'ABSPATH' ) || exit;

final class ModuleRegistry {
	/** @var list<Module> */
	private array $modules;

	private bool $registered = false;

	/**
	 * @param list<Module> $modules Modules in registration order.
	 */
	public function __construct( array $modules ) {
		$this->modules = $modules;
	}

	public function register(): void {
		if ( $this->registered ) {
			return;
		}

		foreach ( $this->modules as $module ) {
			$module->register();
		}

		$this->registered = true;
	}
}

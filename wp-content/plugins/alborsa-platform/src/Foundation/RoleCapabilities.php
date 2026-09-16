<?php
/**
 * Conservative role capability installation.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Foundation;

defined( 'ABSPATH' ) || exit;

final class RoleCapabilities {
	public const MANAGE_PLATFORM = 'manage_alborsa_platform';

	/** @var array<string, list<string>> */
	private array $role_capabilities;

	/**
	 * @param array<string, list<string>> $role_capabilities Capabilities keyed by role slug.
	 */
	public function __construct( array $role_capabilities ) {
		$this->role_capabilities = $role_capabilities;
	}

	public function install(): bool {
		foreach ( $this->role_capabilities as $role_slug => $capabilities ) {
			$role = get_role( $role_slug );

			if ( null === $role ) {
				return false;
			}

			foreach ( $capabilities as $capability ) {
				$role->add_cap( $capability );
			}
		}

		return true;
	}
}

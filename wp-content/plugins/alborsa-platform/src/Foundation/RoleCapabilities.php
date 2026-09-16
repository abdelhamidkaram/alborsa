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
	public const MANAGE_PLATFORM          = 'manage_alborsa_platform';
	public const EDIT_CONTENT             = 'edit_alborsa_contents';
	public const EDIT_OTHERS_CONTENT      = 'edit_others_alborsa_contents';
	public const EDIT_PRIVATE_CONTENT     = 'edit_private_alborsa_contents';
	public const EDIT_PUBLISHED_CONTENT   = 'edit_published_alborsa_contents';
	public const PUBLISH_CONTENT          = 'publish_alborsa_contents';
	public const READ_PRIVATE_CONTENT     = 'read_private_alborsa_contents';
	public const DELETE_CONTENT           = 'delete_alborsa_contents';
	public const DELETE_OTHERS_CONTENT    = 'delete_others_alborsa_contents';
	public const DELETE_PRIVATE_CONTENT   = 'delete_private_alborsa_contents';
	public const DELETE_PUBLISHED_CONTENT = 'delete_published_alborsa_contents';

	public const CONTENT_EDITOR_CAPABILITIES = array(
		self::EDIT_CONTENT,
		self::EDIT_OTHERS_CONTENT,
		self::EDIT_PRIVATE_CONTENT,
		self::EDIT_PUBLISHED_CONTENT,
		self::PUBLISH_CONTENT,
		self::READ_PRIVATE_CONTENT,
		self::DELETE_CONTENT,
		self::DELETE_OTHERS_CONTENT,
		self::DELETE_PRIVATE_CONTENT,
		self::DELETE_PUBLISHED_CONTENT,
	);

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

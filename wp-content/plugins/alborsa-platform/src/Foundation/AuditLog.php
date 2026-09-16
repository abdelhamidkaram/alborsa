<?php
/**
 * Bounded audit trail for sensitive administrative mutations.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Foundation;

defined( 'ABSPATH' ) || exit;

final class AuditLog {
	public const OPTION = 'alborsa_platform_audit_log';
	private const LIMIT = 200;

	public function record( string $action, string $object_type, int $object_id ): void {
		$action      = sanitize_key( $action );
		$object_type = sanitize_key( $object_type );

		if ( '' === $action || '' === $object_type || $object_id < 1 ) {
			return;
		}

		$entries = get_option( self::OPTION, array() );
		$entries = is_array( $entries ) ? $entries : array();
		array_unshift(
			$entries,
			array(
				'action'      => $action,
				'object_type' => $object_type,
				'object_id'   => $object_id,
				'actor_id'    => get_current_user_id(),
				'occurred_at' => current_time( 'mysql', true ),
			)
		);

		update_option( self::OPTION, array_slice( $entries, 0, self::LIMIT ), false );
	}
}

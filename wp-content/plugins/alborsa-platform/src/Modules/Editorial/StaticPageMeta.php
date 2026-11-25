<?php
/**
 * Static page presentation metadata.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\Editorial;

defined( 'ABSPATH' ) || exit;

final class StaticPageMeta {
	public const ENABLED = 'alb_static_page_layout';

	public function register(): void {
		register_post_meta(
			'page',
			self::ENABLED,
			array(
				'type'              => 'boolean',
				'single'            => true,
				'default'           => false,
				'show_in_rest'      => true,
				'sanitize_callback' => array( $this, 'sanitize_boolean' ),
				'auth_callback'     => array( $this, 'can_edit' ),
			)
		);
	}

	public function sanitize_boolean( mixed $raw_value ): bool {
		return in_array( $raw_value, array( true, 1, '1', 'true', 'on' ), true );
	}

	public function can_edit( bool $allowed, string $meta_key, int $post_id ): bool {
		unset( $allowed, $meta_key );

		return current_user_can( 'edit_post', $post_id );
	}

	public function is_enabled( int $post_id ): bool {
		return $post_id > 0 && $this->sanitize_boolean( get_post_meta( $post_id, self::ENABLED, true ) );
	}
}

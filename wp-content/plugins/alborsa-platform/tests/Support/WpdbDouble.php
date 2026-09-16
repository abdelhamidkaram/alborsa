<?php
/**
 * Test double for WordPress wpdb.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Support;

/**
 * Minimal wpdb test double for isolated testing.
 */
class WpdbDouble {
	/**
	 * Table prefix.
	 *
	 * @var string
	 */
	public string $prefix = 'wp_';

	/**
	 * Last database error.
	 *
	 * @var string
	 */
	public string $last_error = '';

	/**
	 * Prepares SQL query.
	 *
	 * @param string $query Query template.
	 * @param mixed  ...$args Query arguments.
	 * @return string
	 */
	public function prepare( string $query, mixed ...$args ): string {
		return sprintf( str_replace( '%s', "'%s'", $query ), ...$args );
	}

	/**
	 * Escapes text for LIKE query.
	 *
	 * @param string $text Text to escape.
	 * @return string
	 */
	public function esc_like( string $text ): string {
		return addcslashes( $text, '_%\\' );
	}

	/**
	 * Returns charset collate.
	 *
	 * @return string
	 */
	public function get_charset_collate(): string {
		return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
	}

	/**
	 * Last executed query.
	 *
	 * @var string|null
	 */
	public ?string $last_query = null;

	/**
	 * Retrieves variable.
	 *
	 * @param string|null $query SQL query.
	 * @param int         $x Column offset (ignored in test double).
	 * @param int         $y Row offset (ignored in test double).
	 * @return mixed
	 */
	public function get_var( ?string $query = null, int $x = 0, int $y = 0 ): mixed {
		unset( $x, $y );
		$this->last_query = $query;

		return null;
	}
}

if ( ! class_exists( 'wpdb' ) ) {
	class_alias( WpdbDouble::class, 'wpdb' );
}

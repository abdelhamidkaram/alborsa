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
		$query = str_replace( '%i', '`%s`', $query );

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
	 * Last inserted row data.
	 *
	 * @var array<string, mixed>|null
	 */
	public ?array $last_insert_data = null;

	/**
	 * Last insert formats.
	 *
	 * @var list<string>|null
	 */
	public ?array $last_insert_formats = null;

	/**
	 * Simulated inserted row identifier.
	 *
	 * @var int
	 */
	public int $insert_id = 0;

	/**
	 * Inserts a row.
	 *
	 * @param string               $table Table name.
	 * @param array<string, mixed> $data Row data.
	 * @param list<string>         $format Value formats.
	 * @return int|false
	 */
	public function insert( string $table, array $data, array $format = array() ): int|false {
		$this->last_query          = 'INSERT INTO ' . $table;
		$this->last_insert_data    = $data;
		$this->last_insert_formats = $format;

		return 1;
	}

	/**
	 * Executes a query.
	 *
	 * @param string $query SQL query.
	 * @return int|false
	 */
	public function query( string $query ): int|false {
		$this->last_query = $query;

		return 1;
	}

	/**
	 * Retrieves result rows.
	 *
	 * @param string      $query SQL query.
	 * @param string|int  $output Output format.
	 * @return array<int, array<string, mixed>>|null
	 */
	public function get_results( string $query, string|int $output = 'OBJECT' ): ?array {
		unset( $output );
		$this->last_query = $query;

		return array();
	}

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

<?php
/**
 * Test double for WordPress WP_REST_Response.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Support;

/**
 * Minimal WP_REST_Response test double.
 */
class WPRestResponseDouble {
	/** @var array<string, string> */
	private array $headers = array();

	public function __construct(
		private mixed $data = null,
		private int $status = 200
	) {
	}

	public function header( string $key, string $value, bool $replace = true ): void {
		unset( $replace );
		$this->headers[ $key ] = $value;
	}

	public function get_data(): mixed {
		return $this->data;
	}

	public function get_status(): int {
		return $this->status;
	}

	/** @return array<string, string> */
	public function get_headers(): array {
		return $this->headers;
	}
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
	class_alias( WPRestResponseDouble::class, 'WP_REST_Response' );
}

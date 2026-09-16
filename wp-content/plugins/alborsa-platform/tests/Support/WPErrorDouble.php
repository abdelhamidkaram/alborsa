<?php
/**
 * Test double for WordPress WP_Error.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Support;

/**
 * Minimal WP_Error test double.
 */
class WPErrorDouble {
	/**
	 * Error code.
	 *
	 * @var string|int
	 */
	protected string|int $code = '';

	/**
	 * Error message.
	 *
	 * @var string
	 */
	protected string $message = '';

	/**
	 * Constructor.
	 *
	 * @param string|int $code Error code.
	 * @param string     $message Error message.
	 */
	public function __construct( string|int $code = '', string $message = '' ) {
		$this->code    = $code;
		$this->message = $message;
	}

	/**
	 * Retrieves the error code.
	 *
	 * @return string|int
	 */
	public function get_error_code(): string|int {
		return $this->code;
	}

	/**
	 * Retrieves the error message.
	 *
	 * @return string
	 */
	public function get_error_message(): string {
		return $this->message;
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	class_alias( WPErrorDouble::class, 'WP_Error' );
}

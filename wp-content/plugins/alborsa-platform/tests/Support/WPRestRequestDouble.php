<?php
/**
 * Test double for WordPress WP_REST_Request.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Support;

/**
 * Minimal WP_REST_Request test double.
 */
class WPRestRequestDouble {
	/** @var array<string, mixed> */
	private array $parameters;

	/**
	 * @param array<string, mixed> $parameters Request parameters.
	 */
	public function __construct( array $parameters = array() ) {
		$this->parameters = $parameters;
	}

	public function get_param( string $key ): mixed {
		return $this->parameters[ $key ] ?? null;
	}

	/** @return array<string, mixed> */
	public function get_json_params(): array {
		$json = $this->parameters['_json'] ?? null;

		return is_array( $json ) ? $json : array();
	}
}

if ( ! class_exists( 'WP_REST_Request' ) ) {
	class_alias( WPRestRequestDouble::class, 'WP_REST_Request' );
}

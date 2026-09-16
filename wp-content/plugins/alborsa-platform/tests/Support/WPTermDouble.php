<?php
/**
 * Test double for WordPress WP_Term.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Support;

/**
 * Minimal WP_Term test double.
 */
class WPTermDouble {
	public string $slug;

	public function __construct( object $term ) {
		$this->slug = (string) $term->slug;
	}
}

if ( ! class_exists( 'WP_Term' ) ) {
	class_alias( WPTermDouble::class, 'WP_Term' );
}

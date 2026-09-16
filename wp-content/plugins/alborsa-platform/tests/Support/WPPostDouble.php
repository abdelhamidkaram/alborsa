<?php
/**
 * Test double for WordPress WP_Post.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Support;

/**
 * Minimal WP_Post test double.
 */
class WPPostDouble {
	public int $ID;
	public string $post_type;
	public string $post_name;

	public function __construct( object $post ) {
		$this->ID        = (int) $post->ID;
		$this->post_type = (string) $post->post_type;
		$this->post_name = (string) $post->post_name;
	}
}

if ( ! class_exists( 'WP_Post' ) ) {
	class_alias( WPPostDouble::class, 'WP_Post' );
}

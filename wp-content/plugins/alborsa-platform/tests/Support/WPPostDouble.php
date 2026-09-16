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
	public string $post_status;
	public string $post_content;

	public function __construct( object $post ) {
		$this->ID           = (int) $post->ID;
		$this->post_type    = (string) $post->post_type;
		$this->post_name    = (string) $post->post_name;
		$this->post_status  = property_exists( $post, 'post_status' ) ? (string) $post->post_status : 'publish';
		$this->post_content = property_exists( $post, 'post_content' ) ? (string) $post->post_content : '';
	}
}

if ( ! class_exists( 'WP_Post' ) ) {
	class_alias( WPPostDouble::class, 'WP_Post' );
}

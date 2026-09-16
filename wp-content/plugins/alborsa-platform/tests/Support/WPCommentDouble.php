<?php
/**
 * Test double for WordPress WP_Comment.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Support;

/**
 * Minimal WP_Comment test double.
 */
class WPCommentDouble {
	public string $comment_ID;
	public string $comment_author;
	public string $comment_content;
	public string $comment_date_gmt;

	public function __construct( object $comment ) {
		$this->comment_ID       = (string) $comment->comment_ID;
		$this->comment_author   = (string) $comment->comment_author;
		$this->comment_content  = (string) $comment->comment_content;
		$this->comment_date_gmt = (string) $comment->comment_date_gmt;
	}
}

if ( ! class_exists( 'WP_Comment' ) ) {
	class_alias( WPCommentDouble::class, 'WP_Comment' );
}

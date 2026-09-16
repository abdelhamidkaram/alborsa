<?php
/**
 * Test double for WordPress WP_User.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Support;

final class WPUserDouble {
	public int $ID;
	public string $display_name;

	public function __construct( object $user ) {
		$this->ID           = (int) $user->ID;
		$this->display_name = (string) $user->display_name;
	}
}

if ( ! class_exists( 'WP_User' ) ) {
	class_alias( WPUserDouble::class, 'WP_User' );
}

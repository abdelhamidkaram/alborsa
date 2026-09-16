<?php
/**
 * Shared isolated unit test setup.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

abstract class UnitTestCase extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'wp_parse_url' )->alias(
			// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Implements the isolated wp_parse_url boundary.
			static fn( string $url, int $component = -1 ): mixed => parse_url( $url, $component )
		);
	}

	protected function tearDown(): void {
		$this->set_wpdb_for_test( null );
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Sets the global wpdb instance for isolated test execution.
	 *
	 * @param \wpdb|null $wpdb Test double or null.
	 */
	protected function set_wpdb_for_test( ?\wpdb $wpdb ): void {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Isolated unit test double replacement.
		$GLOBALS['wpdb'] = $wpdb;
	}
}

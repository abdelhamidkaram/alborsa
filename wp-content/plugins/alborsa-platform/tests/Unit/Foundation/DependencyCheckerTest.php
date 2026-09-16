<?php
/**
 * Soft dependency checker tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Foundation;

use Alborsa\Platform\Foundation\DependencyChecker;
use Alborsa\Platform\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class DependencyCheckerTest extends UnitTestCase {
	/**
	 * @return array<string, array{string, string}>
	 */
	public static function active_plugin_files(): array {
		return array(
			'Polylang'         => array( 'polylang', 'polylang/polylang.php' ),
			'Yoast SEO'        => array( 'yoast-seo', 'wordpress-seo/wp-seo.php' ),
			'Breeze'           => array( 'breeze', 'breeze/breeze.php' ),
			'Object Cache Pro' => array( 'object-cache-pro', 'redis-cache-pro/redis-cache-pro.php' ),
			'Antispam Bee'     => array( 'antispam-bee', 'antispam-bee/antispam_bee.php' ),
			'WP 2FA'           => array( 'wp-2fa', 'wp-2fa/wp-2fa.php' ),
			'WPS Hide Login'   => array( 'wps-hide-login', 'wps-hide-login/wps-hide-login.php' ),
		);
	}

	#[DataProvider( 'active_plugin_files' )]
	public function test_active_plugin_file_is_reported_available( string $key, string $plugin_file ): void {
		$checker  = new DependencyChecker( $this->definitions(), array( $plugin_file ) );
		$statuses = $checker->statuses();

		self::assertTrue( $statuses[ $key ]['available'] );
	}

	public function test_inactive_plugins_are_reported_without_loading_their_apis(): void {
		$checker = new DependencyChecker( $this->definitions(), array() );

		foreach ( $checker->statuses() as $status ) {
			self::assertFalse( $status['available'] );
		}
	}

	/**
	 * @return array<string, array{label: string, category: string, plugin_files: list<string>, runtime_markers: list<array{string, string}>}>
	 */
	private function definitions(): array {
		/** @var array<string, array{label: string, category: string, plugin_files: list<string>, runtime_markers: list<array{string, string}>}> $definitions */
		$definitions = require ALBORSA_PLATFORM_PATH . 'config/dependencies.php';

		foreach ( $definitions as &$definition ) {
			$definition['runtime_markers'] = array();
		}
		unset( $definition );

		return $definitions;
	}
}

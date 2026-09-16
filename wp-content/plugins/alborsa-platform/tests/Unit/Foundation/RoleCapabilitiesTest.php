<?php
/**
 * Role capability tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Foundation;

use Alborsa\Platform\Foundation\RoleCapabilities;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;

final class RoleCapabilitiesTest extends UnitTestCase {
	public function test_configuration_keeps_platform_management_admin_only(): void {
		/** @var array<string, list<string>> $configuration */
		$configuration = require ALBORSA_PLATFORM_PATH . 'config/capabilities.php';

		self::assertContains( RoleCapabilities::MANAGE_PLATFORM, $configuration['administrator'] );
		self::assertNotContains( RoleCapabilities::MANAGE_PLATFORM, $configuration['editor'] );
		self::assertSame( RoleCapabilities::CONTENT_EDITOR_CAPABILITIES, $configuration['editor'] );

		foreach ( RoleCapabilities::CONTENT_EDITOR_CAPABILITIES as $capability ) {
			self::assertContains( $capability, $configuration['administrator'] );
		}
	}

	public function test_install_adds_every_configured_capability(): void {
		$role = new class() {
			/** @var list<string> */
			public array $capabilities = array();

			public function add_cap( string $capability ): void {
				$this->capabilities[] = $capability;
			}
		};

		Functions\when( 'get_role' )->justReturn( $role );
		$installer = new RoleCapabilities(
			array( 'editor' => array( RoleCapabilities::EDIT_CONTENT, RoleCapabilities::PUBLISH_CONTENT ) )
		);

		self::assertTrue( $installer->install() );
		self::assertSame(
			array( RoleCapabilities::EDIT_CONTENT, RoleCapabilities::PUBLISH_CONTENT ),
			$role->capabilities
		);
	}

	public function test_install_fails_when_configured_role_is_missing(): void {
		Functions\when( 'get_role' )->justReturn( null );
		$installer = new RoleCapabilities(
			array( 'missing-role' => array( RoleCapabilities::EDIT_CONTENT ) )
		);

		self::assertFalse( $installer->install() );
	}
}

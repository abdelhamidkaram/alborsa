<?php
/**
 * Foundation module wiring.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Foundation;

defined( 'ABSPATH' ) || exit;

final class FoundationModule implements Module {
	public function register(): void {
		add_action( 'init', array( $this, 'initialize' ), 0 );
		( new RewriteRules() )->register();
	}

	public function initialize(): void {
		load_plugin_textdomain(
			'alborsa-platform',
			false,
			dirname( plugin_basename( ALBORSA_PLATFORM_FILE ) ) . '/languages'
		);

		$dependency_checker = $this->dependency_checker();
		$migration_runner   = MigrationRunner::from_configuration();
		$migration_error    = $migration_runner->run();

		( new AdminNotice( $dependency_checker, $migration_runner, $migration_error ) )->register();
		( new SiteHealth( $dependency_checker, $migration_runner, $migration_error ) )->register();

		$this->register_cli( $dependency_checker, $migration_runner );
	}

	private function dependency_checker(): DependencyChecker {
		/** @var array<string, array{label: string, category: string, plugin_files: list<string>, runtime_markers: list<array{string, string}>}> $definitions */
		$definitions = require dirname( __DIR__, 2 ) . '/config/dependencies.php';

		return new DependencyChecker( $definitions );
	}

	private function register_cli(
		DependencyChecker $dependency_checker,
		MigrationRunner $migration_runner
	): void {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! class_exists( 'WP_CLI' ) ) {
			return;
		}

		\WP_CLI::add_command(
			'alborsa health',
			new HealthCommand( $dependency_checker, $migration_runner ),
			array(
				'shortdesc' => __( 'Checks the Alborsa Platform foundation.', 'alborsa-platform' ),
			)
		);
	}
}

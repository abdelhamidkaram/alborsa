<?php
/**
 * WP-CLI Foundation health command.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Foundation;

defined( 'ABSPATH' ) || exit;

final class HealthCommand {
	private DependencyChecker $dependency_checker;
	private MigrationRunner $migration_runner;

	public function __construct(
		DependencyChecker $dependency_checker,
		MigrationRunner $migration_runner
	) {
		$this->dependency_checker = $dependency_checker;
		$this->migration_runner   = $migration_runner;
	}

	public function __invoke(): void {
		$missing_required = false;
		$missing_other    = false;

		foreach ( $this->dependency_checker->statuses() as $status ) {
			$this->write_dependency( $status );
			$missing_required = $missing_required || ( 'required' === $status['category'] && ! $status['available'] );
			$missing_other    = $missing_other || ( 'required' !== $status['category'] && ! $status['available'] );
		}

		if ( ! $this->migration_runner->is_current() ) {
			\WP_CLI::error( __( 'The Alborsa Platform Foundation migration is not current.', 'alborsa-platform' ) );
		}

		if ( $missing_required ) {
			\WP_CLI::error( __( 'Required production integrations are inactive.', 'alborsa-platform' ) );
		}

		if ( $missing_other ) {
			\WP_CLI::warning( __( 'One or more recommended or optional integrations are inactive.', 'alborsa-platform' ) );
		}

		\WP_CLI::success( __( 'The Alborsa Platform Foundation is healthy.', 'alborsa-platform' ) );
	}

	/**
	 * @param array{key: string, label: string, category: string, available: bool} $status Dependency status.
	 */
	private function write_dependency( array $status ): void {
		$state = $status['available'] ? __( 'available', 'alborsa-platform' ) : __( 'inactive', 'alborsa-platform' );

		\WP_CLI::log(
			sprintf(
				/* translators: 1: plugin name, 2: availability, 3: dependency category. */
				__( '%1$s: %2$s (%3$s)', 'alborsa-platform' ),
				$status['label'],
				$state,
				$this->category_label( $status['category'] )
			)
		);
	}

	private function category_label( string $category ): string {
		if ( 'required' === $category ) {
			return __( 'required', 'alborsa-platform' );
		}

		if ( 'recommended' === $category ) {
			return __( 'recommended', 'alborsa-platform' );
		}

		return __( 'optional', 'alborsa-platform' );
	}
}

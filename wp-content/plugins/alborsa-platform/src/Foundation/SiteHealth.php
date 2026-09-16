<?php
/**
 * WordPress Site Health integration.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Foundation;

use WP_Error;

defined( 'ABSPATH' ) || exit;

final class SiteHealth {
	private DependencyChecker $dependency_checker;
	private MigrationRunner $migration_runner;
	private ?WP_Error $migration_error;

	public function __construct(
		DependencyChecker $dependency_checker,
		MigrationRunner $migration_runner,
		?WP_Error $migration_error
	) {
		$this->dependency_checker = $dependency_checker;
		$this->migration_runner   = $migration_runner;
		$this->migration_error    = $migration_error;
	}

	public function register(): void {
		add_filter( 'site_status_tests', array( $this, 'add_tests' ) );
	}

	/**
	 * @param array<string, array<string, array<string, mixed>>> $tests Site Health tests.
	 * @return array<string, array<string, array<string, mixed>>>
	 */
	public function add_tests( array $tests ): array {
		$tests['direct']['alborsa_platform_dependencies'] = array(
			'label' => __( 'Alborsa Platform integrations', 'alborsa-platform' ),
			'test'  => array( $this, 'test_dependencies' ),
		);
		$tests['direct']['alborsa_platform_migrations']   = array(
			'label' => __( 'Alborsa Platform migrations', 'alborsa-platform' ),
			'test'  => array( $this, 'test_migrations' ),
		);

		return $tests;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function test_dependencies(): array {
		$statuses = $this->dependency_checker->statuses();
		$health   = $this->dependency_health( $statuses );

		return $this->site_health_result(
			'alborsa_platform_dependencies',
			$health['status'],
			$health['label'],
			$this->dependency_description( $statuses )
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function test_migrations(): array {
		if ( is_wp_error( $this->migration_error ) ) {
			return $this->site_health_result(
				'alborsa_platform_migrations',
				'critical',
				__( 'The Alborsa Platform migration failed', 'alborsa-platform' ),
				'<p>' . esc_html( $this->migration_error->get_error_message() ) . '</p>'
			);
		}

		if ( ! $this->migration_runner->is_current() ) {
			return $this->site_health_result(
				'alborsa_platform_migrations',
				'critical',
				__( 'The Alborsa Platform migration is pending', 'alborsa-platform' ),
				'<p>' . esc_html__( 'The stored Foundation schema version is behind the plugin version.', 'alborsa-platform' ) . '</p>'
			);
		}

		return $this->site_health_result(
			'alborsa_platform_migrations',
			'good',
			__( 'The Alborsa Platform migration is current', 'alborsa-platform' ),
			'<p>' . esc_html__( 'Foundation migrations have completed successfully.', 'alborsa-platform' ) . '</p>'
		);
	}

	/**
	 * @param array<string, array{key: string, label: string, category: string, available: bool}> $statuses Dependency statuses.
	 * @return array{status: string, label: string}
	 */
	private function dependency_health( array $statuses ): array {
		foreach ( $statuses as $status ) {
			if ( 'required' === $status['category'] && ! $status['available'] ) {
				return array(
					'status' => 'critical',
					'label'  => __( 'Required Alborsa Platform integrations are missing', 'alborsa-platform' ),
				);
			}
		}

		foreach ( $statuses as $status ) {
			if ( 'recommended' === $status['category'] && ! $status['available'] ) {
				return array(
					'status' => 'recommended',
					'label'  => __( 'Some recommended Alborsa Platform integrations are missing', 'alborsa-platform' ),
				);
			}
		}

		return array(
			'status' => 'good',
			'label'  => __( 'Alborsa Platform production integrations are available', 'alborsa-platform' ),
		);
	}

	/**
	 * @param array<string, array{key: string, label: string, category: string, available: bool}> $statuses Dependency statuses.
	 */
	private function dependency_description( array $statuses ): string {
		$description = '<p>' . esc_html__( 'These checks only verify availability; versions, licenses, settings, and service health still require an environment audit.', 'alborsa-platform' ) . '</p><ul>';

		foreach ( $statuses as $status ) {
			$state        = $status['available'] ? __( 'available', 'alborsa-platform' ) : __( 'inactive', 'alborsa-platform' );
			$description .= '<li>' . esc_html(
				sprintf(
					/* translators: 1: plugin name, 2: availability. */
					__( '%1$s: %2$s', 'alborsa-platform' ),
					$status['label'],
					$state
				)
			) . '</li>';
		}

		return $description . '</ul>';
	}

	/**
	 * @return array<string, mixed>
	 */
	private function site_health_result(
		string $test,
		string $status,
		string $label,
		string $description
	): array {
		return array(
			'label'       => $label,
			'status'      => $status,
			'badge'       => array(
				'label' => __( 'Alborsa Platform', 'alborsa-platform' ),
				'color' => 'blue',
			),
			'description' => $description,
			'actions'     => '',
			'test'        => $test,
		);
	}
}

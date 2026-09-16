<?php
/**
 * Actionable Foundation administration notice.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Foundation;

use WP_Error;

defined( 'ABSPATH' ) || exit;

final class AdminNotice {
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
		add_action( 'admin_notices', array( $this, 'render' ) );
	}

	public function render(): void {
		if ( ! $this->can_view_notice() ) {
			return;
		}

		$statuses = $this->dependency_checker->statuses();
		$messages = $this->messages( $statuses );

		if ( array() === $messages ) {
			return;
		}

		$notice_class = $this->has_critical_issue( $statuses ) ? 'notice notice-error' : 'notice notice-warning';

		echo '<div class="' . esc_attr( $notice_class ) . '"><p><strong>';
		echo esc_html__( 'Alborsa Platform needs attention', 'alborsa-platform' );
		echo '</strong></p><ul>';

		foreach ( $messages as $message ) {
			echo '<li>' . esc_html( $message ) . '</li>';
		}

		echo '</ul></div>';
	}

	private function can_view_notice(): bool {
		return current_user_can( RoleCapabilities::MANAGE_PLATFORM ) || current_user_can( 'manage_options' );
	}

	/**
	 * @param array<string, array{key: string, label: string, category: string, available: bool}> $statuses Dependency statuses.
	 * @return list<string>
	 */
	private function messages( array $statuses ): array {
		$messages = array();

		if ( is_wp_error( $this->migration_error ) ) {
			$messages[] = $this->migration_error->get_error_message();
		} elseif ( ! $this->migration_runner->is_current() ) {
			$messages[] = __( 'The Foundation migration is not current.', 'alborsa-platform' );
		}

		return array_merge( $messages, $this->dependency_messages( $statuses ) );
	}

	/**
	 * @param array<string, array{key: string, label: string, category: string, available: bool}> $statuses Dependency statuses.
	 * @return list<string>
	 */
	private function dependency_messages( array $statuses ): array {
		$missing_required = $this->missing_labels( 'required', $statuses );
		$missing_other    = array_merge(
			$this->missing_labels( 'recommended', $statuses ),
			$this->missing_labels( 'optional', $statuses )
		);
		$messages         = array();

		if ( array() !== $missing_required ) {
			$messages[] = sprintf(
				/* translators: %s: comma-separated plugin names. */
				__( 'Required production integrations are inactive: %s.', 'alborsa-platform' ),
				implode( ', ', $missing_required )
			);
		}

		if ( array() !== $missing_other ) {
			$messages[] = sprintf(
				/* translators: %s: comma-separated plugin names. */
				__( 'Other expected integrations are inactive: %s. The platform will continue without them.', 'alborsa-platform' ),
				implode( ', ', $missing_other )
			);
		}

		return $messages;
	}

	/**
	 * @param array<string, array{key: string, label: string, category: string, available: bool}> $statuses Dependency statuses.
	 * @return list<string>
	 */
	private function missing_labels( string $category, array $statuses ): array {
		$labels = array();

		foreach ( $statuses as $status ) {
			if ( $category === $status['category'] && ! $status['available'] ) {
				$labels[] = $status['label'];
			}
		}

		return $labels;
	}

	/**
	 * @param array<string, array{key: string, label: string, category: string, available: bool}> $statuses Dependency statuses.
	 */
	private function has_critical_issue( array $statuses ): bool {
		return is_wp_error( $this->migration_error ) || array() !== $this->missing_labels( 'required', $statuses );
	}
}

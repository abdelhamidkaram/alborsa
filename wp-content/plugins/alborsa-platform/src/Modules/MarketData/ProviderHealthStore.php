<?php
/**
 * Persists non-sensitive quote synchronization health.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\MarketData;

use WP_Error;

defined( 'ABSPATH' ) || exit;

final class ProviderHealthStore {
	public const OPTION = 'alborsa_platform_quote_provider_health';

	public function record_success( string $provider_key, int $assets_saved ): void {
		$this->update(
			$provider_key,
			array(
				'last_success_at' => $this->now(),
				'last_error_code' => '',
				'assets_saved'    => max( 0, $assets_saved ),
			)
		);
	}

	public function record_failure( string $provider_key, WP_Error $error ): void {
		$this->update(
			$provider_key,
			array(
				'last_failure_at' => $this->now(),
				'last_error_code' => sanitize_key( (string) $error->get_error_code() ),
			)
		);
	}

	/**
	 * @return array<string, int|string>
	 */
	public function get( string $provider_key ): array {
		$health = get_option( self::OPTION, array() );

		if ( ! is_array( $health ) || ! isset( $health[ $provider_key ] ) || ! is_array( $health[ $provider_key ] ) ) {
			return array();
		}

		return $health[ $provider_key ];
	}

	public function failed_after( string $provider_key, string $received_at ): bool {
		$state           = $this->get( $provider_key );
		$last_failure_at = is_string( $state['last_failure_at'] ?? null ) ? $state['last_failure_at'] : '';

		return '' !== $last_failure_at && $last_failure_at > $received_at;
	}

	/**
	 * @param array<string, int|string> $changes Health fields to merge.
	 */
	private function update( string $provider_key, array $changes ): void {
		if ( ! preg_match( '/\A[a-z0-9_-]{1,32}\z/', $provider_key ) ) {
			return;
		}

		$health = get_option( self::OPTION, array() );
		$health = is_array( $health ) ? $health : array();
		$state  = isset( $health[ $provider_key ] ) && is_array( $health[ $provider_key ] )
			? $health[ $provider_key ]
			: array();

		$health[ $provider_key ] = array_merge( $state, $changes );
		update_option( self::OPTION, $health, false );
	}

	private function now(): string {
		return current_time( 'mysql', true );
	}
}

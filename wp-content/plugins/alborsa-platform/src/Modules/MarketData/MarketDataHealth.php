<?php
/**
 * Site Health diagnostics for quote-provider runtime state.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\MarketData;

defined( 'ABSPATH' ) || exit;

final class MarketDataHealth {
	public function __construct(
		private QuoteProviderRegistry $providers,
		private ProviderHealthStore $health
	) {
	}

	public function register(): void {
		add_filter( 'site_status_tests', array( $this, 'add_test' ) );
	}

	/**
	 * @param array<string, array<string, array<string, mixed>>> $tests Site Health tests.
	 * @return array<string, array<string, array<string, mixed>>>
	 */
	public function add_test( array $tests ): array {
		$tests['direct']['alborsa_platform_market_data'] = array(
			'label' => __( 'Alborsa Platform market data', 'alborsa-platform' ),
			'test'  => array( $this, 'test' ),
		);

		return $tests;
	}

	/** @return array<string, mixed> */
	public function test(): array {
		$provider_keys = $this->providers->keys();

		if ( array() === $provider_keys ) {
			return $this->result(
				'recommended',
				__( 'No licensed quote provider is configured', 'alborsa-platform' ),
				__( 'The provider-neutral market data runtime is ready, but live synchronization remains disabled until a provider is approved and connected.', 'alborsa-platform' )
			);
		}

		foreach ( $provider_keys as $provider_key ) {
			$state        = $this->health->get( $provider_key );
			$last_success = is_string( $state['last_success_at'] ?? null ) ? $state['last_success_at'] : '';
			$last_failure = is_string( $state['last_failure_at'] ?? null ) ? $state['last_failure_at'] : '';

			if ( '' === $last_success || $last_failure > $last_success ) {
				return $this->result(
					'recommended',
					__( 'Quote synchronization needs attention', 'alborsa-platform' ),
					__( 'At least one configured provider has not completed a successful synchronization after its latest failure.', 'alborsa-platform' )
				);
			}
		}

		return $this->result(
			'good',
			__( 'Quote synchronization is healthy', 'alborsa-platform' ),
			__( 'Every configured quote provider has a successful synchronization after its latest recorded failure.', 'alborsa-platform' )
		);
	}

	/** @return array<string, mixed> */
	private function result( string $status, string $label, string $description ): array {
		return array(
			'label'       => $label,
			'status'      => $status,
			'badge'       => array(
				'label' => __( 'Alborsa Platform', 'alborsa-platform' ),
				'color' => 'blue',
			),
			'description' => '<p>' . esc_html( $description ) . '</p>',
			'actions'     => '',
			'test'        => 'alborsa_platform_market_data',
		);
	}
}

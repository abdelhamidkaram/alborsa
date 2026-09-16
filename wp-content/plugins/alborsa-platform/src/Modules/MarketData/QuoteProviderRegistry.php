<?php
/**
 * Discovers provider adapters without selecting a commercial provider.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\MarketData;

use WP_Error;

defined( 'ABSPATH' ) || exit;

final class QuoteProviderRegistry {
	/** @var array<string, QuoteProviderInterface> */
	private array $providers = array();

	/** @param array<array-key, mixed> $providers Candidate provider adapters. */
	public function __construct( array $providers ) {
		foreach ( $providers as $provider ) {
			if ( ! $provider instanceof QuoteProviderInterface
				|| ! preg_match( '/\A[a-z0-9_-]{1,32}\z/', $provider->key() )
			) {
				continue;
			}

			$this->providers[ $provider->key() ] = $provider;
		}
	}

	public static function from_wordpress(): self {
		$providers = apply_filters( 'alborsa_platform_quote_providers', array() );

		return new self( is_array( $providers ) ? $providers : array() );
	}

	public function get( string $provider_key ): QuoteProviderInterface|WP_Error {
		if ( isset( $this->providers[ $provider_key ] ) ) {
			return $this->providers[ $provider_key ];
		}

		return new WP_Error(
			'alborsa_platform_quote_provider_unavailable',
			__( 'The requested quote provider is not configured.', 'alborsa-platform' )
		);
	}

	/**
	 * @return list<string>
	 */
	public function keys(): array {
		return array_keys( $this->providers );
	}
}

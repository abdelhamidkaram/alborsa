<?php
/**
 * Deterministic in-memory quote provider for tests and local development.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\MarketData;

use WP_Error;

defined( 'ABSPATH' ) || exit;

final class MockQuoteProvider implements QuoteProviderInterface {
	private const MAX_BATCH_SIZE = 100;

	/** @var array<string, NormalizedQuote> */
	private array $fixtures;

	/** @param array<string, NormalizedQuote> $fixtures Quotes keyed by provider symbol. */
	public function __construct( array $fixtures ) {
		$this->fixtures = $fixtures;
	}

	public function key(): string {
		return 'mock';
	}

	/**
	 * @param list<string> $provider_symbols Provider-specific symbols.
	 * @return list<NormalizedQuote>|WP_Error
	 */
	public function fetch_quotes( array $provider_symbols ): array|WP_Error {
		$provider_symbols = array_values( array_unique( $provider_symbols ) );

		if ( array() === $provider_symbols || count( $provider_symbols ) > self::MAX_BATCH_SIZE ) {
			return $this->error( 'invalid_batch', __( 'Mock quote batches must contain between 1 and 100 unique symbols.', 'alborsa-platform' ) );
		}

		$quotes = array();

		foreach ( $provider_symbols as $provider_symbol ) {
			if ( '' === $provider_symbol || ! isset( $this->fixtures[ $provider_symbol ] ) ) {
				return $this->error( 'fixture_missing', __( 'A requested mock quote fixture is missing.', 'alborsa-platform' ) );
			}

			$quote = $this->fixtures[ $provider_symbol ];

			if ( $this->key() !== $quote->provider_key() || $provider_symbol !== $quote->provider_symbol() ) {
				return $this->error( 'fixture_mismatch', __( 'A mock quote fixture does not match its provider identity.', 'alborsa-platform' ) );
			}

			$quotes[] = $quote;
		}

		return $quotes;
	}

	private function error( string $suffix, string $message ): WP_Error {
		return new WP_Error( 'alborsa_platform_mock_quote_' . $suffix, $message );
	}
}

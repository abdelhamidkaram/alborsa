<?php
/**
 * Synchronizes normalized quote batches outside frontend requests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\MarketData;

use WP_Error;

defined( 'ABSPATH' ) || exit;

final class QuoteSyncService {
	public function __construct(
		private QuoteRepository $quotes,
		private QuoteCache $cache,
		private ProviderHealthStore $health
	) {
	}

	/**
	 * @param list<array{asset_id: int, mapping: QuoteMapping}> $mappings Asset mappings.
	 * @return array{provider: string, assets_saved: int, symbols_requested: int}|WP_Error
	 */
	public function sync( QuoteProviderInterface $provider, array $mappings ): array|WP_Error {
		$symbols = $this->requested_symbols( $provider, $mappings );

		if ( $symbols instanceof WP_Error ) {
			return $symbols;
		}

		$quotes_by_symbol = $this->fetch_quotes( $provider, $symbols );

		if ( $quotes_by_symbol instanceof WP_Error ) {
			return $quotes_by_symbol;
		}

		$saved = $this->save_quotes( $provider, $mappings, $quotes_by_symbol );

		if ( $saved instanceof WP_Error ) {
			return $saved;
		}

		$this->health->record_success( $provider->key(), count( $mappings ) );

		return array(
			'provider'          => $provider->key(),
			'assets_saved'      => count( $mappings ),
			'symbols_requested' => count( $symbols ),
		);
	}

	/**
	 * @param list<array{asset_id: int, mapping: QuoteMapping}> $mappings Asset mappings.
	 * @return list<string>|WP_Error
	 */
	private function requested_symbols( QuoteProviderInterface $provider, array $mappings ): array|WP_Error {
		if ( array() === $mappings || count( $mappings ) > 100 ) {
			return $this->error( 'invalid_batch', __( 'Quote synchronization requires between 1 and 100 asset mappings.', 'alborsa-platform' ) );
		}

		$symbols = array();

		foreach ( $mappings as $mapping_record ) {
			if ( $mapping_record['asset_id'] <= 0 || $provider->key() !== $mapping_record['mapping']->provider_key() ) {
				return $this->error( 'mapping_mismatch', __( 'A quote mapping does not match the selected provider.', 'alborsa-platform' ) );
			}

			$symbols[] = $mapping_record['mapping']->provider_symbol();
		}

		return array_values( array_unique( $symbols ) );
	}

	/**
	 * @param list<string> $symbols Provider symbols.
	 * @return array<string, NormalizedQuote>|WP_Error
	 */
	private function fetch_quotes( QuoteProviderInterface $provider, array $symbols ): array|WP_Error {
		$fetched = $provider->fetch_quotes( $symbols );

		if ( $fetched instanceof WP_Error ) {
			return $this->record_failure( $provider, $fetched );
		}

		$indexed = $this->index_quotes( $provider, $symbols, $fetched );

		return $indexed instanceof WP_Error ? $this->record_failure( $provider, $indexed ) : $indexed;
	}

	/**
	 * @param list<array{asset_id: int, mapping: QuoteMapping}> $mappings Asset mappings.
	 * @param array<string, NormalizedQuote>                   $quotes_by_symbol Indexed quotes.
	 */
	private function save_quotes(
		QuoteProviderInterface $provider,
		array $mappings,
		array $quotes_by_symbol
	): true|WP_Error {
		foreach ( $mappings as $mapping_record ) {
			$asset_id = $mapping_record['asset_id'];
			$quote    = $quotes_by_symbol[ $mapping_record['mapping']->provider_symbol() ];
			$saved    = $this->quotes->save( $asset_id, $quote );

			if ( $saved instanceof WP_Error ) {
				return $this->record_failure( $provider, $saved );
			}

			$this->cache->set( $asset_id, $provider->key(), $quote->to_array() );
		}

		return true;
	}

	private function record_failure( QuoteProviderInterface $provider, WP_Error $error ): WP_Error {
		$this->health->record_failure( $provider->key(), $error );

		return $error;
	}

	/**
	 * @param list<string>          $expected_symbols Requested provider symbols.
	 * @param list<NormalizedQuote> $quotes Provider response.
	 * @return array<string, NormalizedQuote>|WP_Error
	 */
	private function index_quotes(
		QuoteProviderInterface $provider,
		array $expected_symbols,
		array $quotes
	): array|WP_Error {
		$indexed = array();

		foreach ( $quotes as $quote ) {
			if ( $provider->key() !== $quote->provider_key()
				|| ! in_array( $quote->provider_symbol(), $expected_symbols, true )
				|| isset( $indexed[ $quote->provider_symbol() ] )
			) {
				return $this->error( 'provider_response_mismatch', __( 'The quote provider returned an unexpected quote identity.', 'alborsa-platform' ) );
			}

			$indexed[ $quote->provider_symbol() ] = $quote;
		}

		if ( count( $indexed ) !== count( $expected_symbols ) ) {
			return $this->error( 'provider_response_incomplete', __( 'The quote provider did not return every requested symbol.', 'alborsa-platform' ) );
		}

		return $indexed;
	}

	private function error( string $suffix, string $message ): WP_Error {
		return new WP_Error( 'alborsa_platform_quote_sync_' . $suffix, $message );
	}
}

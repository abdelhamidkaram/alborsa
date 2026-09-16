<?php
/**
 * Public PHP query service for latest market quotes.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\MarketData;

use WP_Error;

defined( 'ABSPATH' ) || exit;

final class QuoteReadService {
	public function __construct(
		private QuoteRepository $quotes,
		private AssetQuoteMappingRepository $mappings,
		private QuoteCache $cache,
		private ProviderHealthStore $health
	) {
	}

	public static function from_wordpress(): self|WP_Error {
		global $wpdb;

		if ( ! $wpdb instanceof \wpdb ) {
			return new WP_Error(
				'alborsa_platform_quote_database_unavailable',
				__( 'The quote database is unavailable.', 'alborsa-platform' )
			);
		}

		return new self(
			new QuoteRepository( $wpdb ),
			new AssetQuoteMappingRepository(),
			new QuoteCache(),
			new ProviderHealthStore()
		);
	}

	public function find_for_asset( int $asset_id ): QuoteReadResult|WP_Error {
		$mapping = $this->mappings->find_for_asset( $asset_id );

		if ( null === $mapping ) {
			return $this->not_found( __( 'No enabled quote source is configured for this asset.', 'alborsa-platform' ) );
		}

		$quote = $this->latest_snapshot( $asset_id, $mapping );

		if ( $quote instanceof WP_Error ) {
			return $quote;
		}

		return $this->read_result( $asset_id, $mapping, $quote );
	}

	/** @return array<string, mixed>|WP_Error */
	private function latest_snapshot( int $asset_id, QuoteMapping $mapping ): array|WP_Error {
		$quote = $this->cache->get( $asset_id, $mapping->provider_key() );

		if ( null !== $quote ) {
			return $quote;
		}

		$quote = $this->quotes->find( $asset_id, $mapping->provider_key() );

		if ( $quote instanceof WP_Error ) {
			return $quote;
		}

		if ( null === $quote ) {
			return $this->not_found( __( 'No quote has been synchronized for this asset.', 'alborsa-platform' ) );
		}

		$this->cache->set( $asset_id, $mapping->provider_key(), $quote );

		return $quote;
	}

	/** @param array<string, mixed> $quote Latest quote snapshot. */
	private function read_result( int $asset_id, QuoteMapping $mapping, array $quote ): QuoteReadResult {
		$received_at     = $this->string_value( $quote['received_at'] ?? null );
		$status          = $this->status( $quote['status'] ?? null );
		$stale           = NormalizedQuote::STALE === $status
			|| $this->health->failed_after( $mapping->provider_key(), $received_at );
		$status          = $stale ? NormalizedQuote::STALE : $status;
		$quote['status'] = $status;
		$quote['stale']  = $stale;

		return new QuoteReadResult(
			$this->public_fields( $asset_id, $mapping, $quote ),
			$this->cache->ttl( $quote )
		);
	}

	/**
	 * @param array<string, mixed> $quote Latest quote snapshot.
	 * @return array<string, mixed>
	 */
	private function public_fields(
		int $asset_id,
		QuoteMapping $mapping,
		array $quote
	): array {
		return array(
			'asset_id'        => $asset_id,
			'price'           => $this->nullable_string( $quote['price'] ?? null ),
			'open'            => $this->nullable_string( $quote['open_price'] ?? null ),
			'previous_close'  => $this->nullable_string( $quote['previous_close'] ?? null ),
			'high'            => $this->nullable_string( $quote['high_price'] ?? null ),
			'low'             => $this->nullable_string( $quote['low_price'] ?? null ),
			'volume'          => $this->nullable_string( $quote['volume'] ?? null ),
			'absolute_change' => $this->nullable_string( $quote['absolute_change'] ?? null ),
			'percent_change'  => $this->nullable_string( $quote['percent_change'] ?? null ),
			'currency'        => $this->nullable_string( $quote['currency'] ?? null ),
			'as_of'           => $this->iso_timestamp( $quote['market_timestamp'] ?? null ),
			'received_at'     => $this->iso_timestamp( $quote['received_at'] ?? null ),
			'delay_seconds'   => $this->delay_seconds( $mapping, $quote ),
			'status'          => $quote['status'],
			'stale'           => $quote['stale'],
			'source'          => $this->source( $mapping ),
		);
	}

	/** @param array<string, mixed> $quote Latest quote snapshot. */
	private function delay_seconds( QuoteMapping $mapping, array $quote ): int {
		$quote_delay = is_numeric( $quote['delay_seconds'] ?? null ) ? (int) $quote['delay_seconds'] : 0;

		return max( $mapping->delay_seconds(), $quote_delay );
	}

	/** @return array{label: string|null, url: string|null} */
	private function source( QuoteMapping $mapping ): array {
		return array(
			'label' => '' === $mapping->attribution_label() ? null : $mapping->attribution_label(),
			'url'   => '' === $mapping->attribution_url() ? null : $mapping->attribution_url(),
		);
	}

	private function status( mixed $status ): string {
		return is_string( $status ) && in_array(
			$status,
			array( NormalizedQuote::FRESH, NormalizedQuote::DELAYED, NormalizedQuote::STALE, NormalizedQuote::UNAVAILABLE ),
			true
		) ? $status : NormalizedQuote::UNAVAILABLE;
	}

	private function iso_timestamp( mixed $timestamp ): ?string {
		$timestamp = $this->string_value( $timestamp );

		return '' === $timestamp ? null : str_replace( ' ', 'T', $timestamp ) . 'Z';
	}

	private function nullable_string( mixed $value ): ?string {
		$value = $this->string_value( $value );

		return '' === $value ? null : $value;
	}

	private function string_value( mixed $value ): string {
		return is_scalar( $value ) ? (string) $value : '';
	}

	private function not_found( string $message ): WP_Error {
		return new WP_Error(
			'alborsa_platform_quote_not_found',
			$message,
			array( 'status' => 404 )
		);
	}
}

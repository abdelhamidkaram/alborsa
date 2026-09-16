<?php
/**
 * Targeted object cache for latest quote snapshots.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\MarketData;

defined( 'ABSPATH' ) || exit;

final class QuoteCache {
	private const GROUP = 'alborsa_platform_quotes';

	/**
	 * @return array<string, mixed>|null
	 */
	public function get( int $asset_id, string $provider_key ): ?array {
		$cached = wp_cache_get( $this->key( $asset_id, $provider_key ), self::GROUP );

		return is_array( $cached ) ? $cached : null;
	}

	/**
	 * @param array<string, mixed> $quote Latest quote row.
	 */
	public function set( int $asset_id, string $provider_key, array $quote ): bool {
		return wp_cache_set(
			$this->key( $asset_id, $provider_key ),
			$quote,
			self::GROUP,
			$this->ttl( $quote )
		);
	}

	public function delete( int $asset_id, string $provider_key ): bool {
		return wp_cache_delete( $this->key( $asset_id, $provider_key ), self::GROUP );
	}

	/** @param array<string, mixed> $quote Latest quote row. */
	public function ttl( array $quote ): int {
		$status = is_string( $quote['status'] ?? null ) ? $quote['status'] : NormalizedQuote::UNAVAILABLE;

		if ( in_array( $status, array( NormalizedQuote::STALE, NormalizedQuote::UNAVAILABLE ), true ) ) {
			return 30;
		}

		$delay_seconds = is_numeric( $quote['delay_seconds'] ?? null ) ? (int) $quote['delay_seconds'] : 0;

		return 0 === $delay_seconds ? 30 : min( 300, max( 60, (int) ceil( $delay_seconds / 3 ) ) );
	}

	private function key( int $asset_id, string $provider_key ): string {
		return 'v1:' . max( 0, $asset_id ) . ':' . $provider_key;
	}
}

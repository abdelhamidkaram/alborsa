<?php
/**
 * Persists the latest normalized quote snapshot per asset and provider.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\MarketData;

use Alborsa\Platform\Foundation\MigrationRunner;
use WP_Error;

defined( 'ABSPATH' ) || exit;

final class QuoteRepository {
	private const COLUMNS = array(
		'asset_id',
		'provider_key',
		'provider_symbol',
		'price',
		'open_price',
		'previous_close',
		'high_price',
		'low_price',
		'volume',
		'absolute_change',
		'percent_change',
		'currency',
		'market_timestamp',
		'received_at',
		'delay_seconds',
		'status',
		'payload_hash',
	);

	private const UPDATE_COLUMNS = array(
		'provider_symbol',
		'price',
		'open_price',
		'previous_close',
		'high_price',
		'low_price',
		'volume',
		'absolute_change',
		'percent_change',
		'currency',
		'market_timestamp',
		'received_at',
		'delay_seconds',
		'status',
		'payload_hash',
	);

	public function __construct( private \wpdb $wpdb ) {
	}

	public function save( int $asset_id, NormalizedQuote $quote ): bool|WP_Error {
		if ( $asset_id <= 0 ) {
			return $this->error( 'invalid_asset', __( 'Quote asset ID must be a positive integer.', 'alborsa-platform' ) );
		}

		$query = $this->upsert_query( $asset_id, $quote );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- upsert_query() binds the table and every non-NULL value.
		$result = $this->wpdb->query( $query );

		if ( false === $result ) {
			return $this->database_error( 'save' );
		}

		return true;
	}

	/**
	 * @return array<string, mixed>|null|WP_Error
	 */
	public function find( int $asset_id, string $provider_key ): array|null|WP_Error {
		if ( $asset_id <= 0 ) {
			return $this->error( 'invalid_asset', __( 'Quote asset ID must be a positive integer.', 'alborsa-platform' ) );
		}

		if ( ! preg_match( '/\A[a-z0-9_-]{1,32}\z/', $provider_key ) ) {
			return $this->error( 'invalid_provider', __( 'Quote provider key is invalid.', 'alborsa-platform' ) );
		}

		$query = $this->wpdb->prepare(
			'SELECT asset_id, provider_key, provider_symbol, price, open_price, previous_close, high_price, low_price, volume, absolute_change, percent_change, currency, market_timestamp, received_at, delay_seconds, status, payload_hash FROM %i WHERE asset_id = %d AND provider_key = %s LIMIT 1',
			$this->table_name(),
			$asset_id,
			$provider_key
		);
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The preceding wpdb::prepare() binds every value.
		$rows = $this->wpdb->get_results( $query, defined( 'ARRAY_A' ) ? ARRAY_A : 'ARRAY_A' );

		if ( null === $rows && '' !== $this->wpdb->last_error ) {
			return $this->database_error( 'read' );
		}

		if ( ! is_array( $rows ) || ! isset( $rows[0] ) || ! is_array( $rows[0] ) ) {
			return null;
		}

		return $rows[0];
	}

	private function upsert_query( int $asset_id, NormalizedQuote $quote ): string {
		$quote_values = $quote->to_array();
		$data         = array_merge( array( 'asset_id' => $asset_id ), $quote_values );
		$placeholders = array();
		$arguments    = array( $this->table_name() );

		foreach ( self::COLUMNS as $column ) {
			$value = $data[ $column ];

			if ( null === $value ) {
				$placeholders[] = 'NULL';
				continue;
			}

			$placeholders[] = in_array( $column, array( 'asset_id', 'delay_seconds' ), true ) ? '%d' : '%s';
			$arguments[]    = $value;
		}

		$updates  = array_map(
			static fn( string $column ): string => $column . ' = VALUES(' . $column . ')',
			self::UPDATE_COLUMNS
		);
		$template = 'INSERT INTO %i (' . implode( ', ', self::COLUMNS ) . ') VALUES ('
			. implode( ', ', $placeholders ) . ') ON DUPLICATE KEY UPDATE ' . implode( ', ', $updates );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Template structure uses class constants; every value is represented by a placeholder.
		return $this->wpdb->prepare( $template, ...$arguments );
	}

	private function table_name(): string {
		return $this->wpdb->prefix . MigrationRunner::MARKET_QUOTES_TABLE;
	}

	private function error( string $suffix, string $message ): WP_Error {
		return new WP_Error( 'alborsa_platform_quote_repository_' . $suffix, $message );
	}

	private function database_error( string $operation ): WP_Error {
		$detail = '' !== $this->wpdb->last_error ? ' ' . $this->wpdb->last_error : '';

		return new WP_Error(
			'alborsa_platform_quote_repository_' . $operation . '_failed',
			sprintf(
				/* translators: 1: database operation, 2: database error detail. */
				__( 'Alborsa Platform could not %1$s the quote.%2$s', 'alborsa-platform' ),
				$operation,
				$detail
			)
		);
	}
}

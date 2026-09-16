<?php
/**
 * Immutable normalized current-quote value object.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\MarketData;

use WP_Error;

defined( 'ABSPATH' ) || exit;

final class NormalizedQuote {
	public const FRESH       = 'fresh';
	public const DELAYED     = 'delayed';
	public const STALE       = 'stale';
	public const UNAVAILABLE = 'unavailable';

	private const STATUSES = array(
		self::FRESH,
		self::DELAYED,
		self::STALE,
		self::UNAVAILABLE,
	);

	private const NON_NEGATIVE_FIELDS = array(
		'price',
		'open_price',
		'previous_close',
		'high_price',
		'low_price',
		'volume',
	);

	private const SIGNED_FIELDS = array(
		'absolute_change',
		'percent_change',
	);

	/** @param array<string, int|string|null> $values Normalized values. */
	private function __construct( private readonly array $values ) {
	}

	/**
	 * @param array<string, mixed> $quote Provider-normalized quote fields.
	 * @return self|WP_Error
	 */
	public static function from_array( array $quote ): self|WP_Error {
		$identity = self::identity( $quote );

		if ( $identity instanceof WP_Error ) {
			return $identity;
		}

		$status = strtolower( trim( self::scalar_string( $quote['status'] ?? self::FRESH ) ) );

		if ( ! in_array( $status, self::STATUSES, true ) ) {
			return self::error( 'invalid_status', __( 'Quote status is invalid.', 'alborsa-platform' ) );
		}

		$numbers = self::numbers( $quote );

		if ( $numbers instanceof WP_Error ) {
			return $numbers;
		}

		$currency = strtoupper( trim( self::scalar_string( $quote['currency'] ?? '' ) ) );

		if ( self::UNAVAILABLE !== $status && ( null === $numbers['price'] || ! preg_match( '/\A[A-Z]{3}\z/', $currency ) ) ) {
			return self::error( 'missing_market_value', __( 'Available quotes require price and a three-letter currency.', 'alborsa-platform' ) );
		}

		if ( '' !== $currency && ! preg_match( '/\A[A-Z]{3}\z/', $currency ) ) {
			return self::error( 'invalid_currency', __( 'Quote currency must contain three letters.', 'alborsa-platform' ) );
		}

		$market_timestamp = self::timestamp( $quote['market_timestamp'] ?? null, 'market_timestamp' );

		if ( $market_timestamp instanceof WP_Error ) {
			return $market_timestamp;
		}

		$received_at = self::timestamp( $quote['received_at'] ?? null, 'received_at' );

		if ( $received_at instanceof WP_Error ) {
			return $received_at;
		}

		$delay_seconds = self::non_negative_integer( $quote['delay_seconds'] ?? 0 );

		if ( null === $delay_seconds ) {
			return self::error( 'invalid_delay', __( 'Quote delay_seconds must be a non-negative integer.', 'alborsa-platform' ) );
		}

		$values       = array_merge(
			$identity,
			$numbers,
			array(
				'currency'         => '' === $currency ? null : $currency,
				'market_timestamp' => $market_timestamp,
				'received_at'      => $received_at,
				'delay_seconds'    => $delay_seconds,
				'status'           => $status,
			)
		);
		$payload_hash = strtolower( trim( self::scalar_string( $quote['payload_hash'] ?? '' ) ) );

		if ( '' !== $payload_hash && ! preg_match( '/\A[a-f0-9]{64}\z/', $payload_hash ) ) {
			return self::error( 'invalid_payload_hash', __( 'Quote payload_hash must be a SHA-256 hexadecimal value.', 'alborsa-platform' ) );
		}

		$values['payload_hash'] = '' === $payload_hash ? self::hash( $values ) : $payload_hash;

		return new self( $values );
	}

	public function provider_key(): string {
		return (string) $this->values['provider_key'];
	}

	public function provider_symbol(): string {
		return (string) $this->values['provider_symbol'];
	}

	public function status(): string {
		return (string) $this->values['status'];
	}

	public function payload_hash(): string {
		return (string) $this->values['payload_hash'];
	}

	/**
	 * @return array<string, int|string|null>
	 */
	public function to_array(): array {
		return $this->values;
	}

	/**
	 * @param array<string, mixed> $quote Raw quote values.
	 * @return array{provider_key: string, provider_symbol: string}|WP_Error
	 */
	private static function identity( array $quote ): array|WP_Error {
		$provider_key    = strtolower( trim( self::scalar_string( $quote['provider_key'] ?? '' ) ) );
		$provider_symbol = trim( self::scalar_string( $quote['provider_symbol'] ?? '' ) );

		if ( ! preg_match( '/\A[a-z0-9_-]{1,32}\z/', $provider_key ) ) {
			return self::error( 'invalid_provider_key', __( 'Quote provider_key is invalid.', 'alborsa-platform' ) );
		}

		if ( '' === $provider_symbol || strlen( $provider_symbol ) > 64 ) {
			return self::error( 'invalid_provider_symbol', __( 'Quote provider_symbol is invalid.', 'alborsa-platform' ) );
		}

		return array(
			'provider_key'    => $provider_key,
			'provider_symbol' => $provider_symbol,
		);
	}

	/**
	 * @param array<string, mixed> $quote Raw quote values.
	 * @return array<string, string|null>|WP_Error
	 */
	private static function numbers( array $quote ): array|WP_Error {
		$numbers = array();

		foreach ( self::NON_NEGATIVE_FIELDS as $field ) {
			$value = self::decimal( $quote[ $field ] ?? null, false );

			if ( $value instanceof WP_Error ) {
				return self::error(
					'invalid_' . $field,
					sprintf(
						/* translators: %s: quote field name. */
						__( '%s must be a non-negative decimal string.', 'alborsa-platform' ),
						$field
					)
				);
			}

			$numbers[ $field ] = $value;
		}

		foreach ( self::SIGNED_FIELDS as $field ) {
			$value = self::decimal( $quote[ $field ] ?? null, true );

			if ( $value instanceof WP_Error ) {
				return self::error(
					'invalid_' . $field,
					sprintf(
						/* translators: %s: quote field name. */
						__( '%s must be a decimal string.', 'alborsa-platform' ),
						$field
					)
				);
			}

			$numbers[ $field ] = $value;
		}

		return $numbers;
	}

	private static function decimal( mixed $value, bool $signed ): string|WP_Error|null {
		if ( null === $value || '' === $value ) {
			return null;
		}

		if ( ! is_string( $value ) && ! is_int( $value ) ) {
			return self::error( 'invalid_decimal', __( 'Quote decimal values must not use floating-point inputs.', 'alborsa-platform' ) );
		}

		$decimal = trim( (string) $value );
		$pattern = $signed ? '/\A-?\d{1,20}(?:\.\d{1,10})?\z/' : '/\A\d{1,20}(?:\.\d{1,10})?\z/';

		if ( ! preg_match( $pattern, $decimal ) ) {
			return self::error( 'invalid_decimal', __( 'Quote decimal value is invalid.', 'alborsa-platform' ) );
		}

		$negative = str_starts_with( $decimal, '-' );
		$unsigned = $negative ? substr( $decimal, 1 ) : $decimal;
		$parts    = explode( '.', $unsigned, 2 );
		$integer  = ltrim( $parts[0], '0' );
		$fraction = isset( $parts[1] ) ? rtrim( $parts[1], '0' ) : '';
		$integer  = '' === $integer ? '0' : $integer;
		$result   = '' === $fraction ? $integer : $integer . '.' . $fraction;

		return $negative && '0' !== $result ? '-' . $result : $result;
	}

	private static function timestamp( mixed $value, string $field ): string|WP_Error {
		$timestamp = trim( self::scalar_string( $value ) );

		if ( '' === $timestamp ) {
			return self::error(
				'missing_' . $field,
				sprintf(
					/* translators: %s: quote timestamp field name. */
					__( '%s is required.', 'alborsa-platform' ),
					$field
				)
			);
		}

		if ( ! preg_match( '/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})\z/', $timestamp ) ) {
			return self::error(
				'invalid_' . $field,
				sprintf(
					/* translators: %s: quote timestamp field name. */
					__( '%s must use an ISO-8601 timestamp with timezone.', 'alborsa-platform' ),
					$field
				)
			);
		}

		try {
			$date = new \DateTimeImmutable( $timestamp );
		} catch ( \Exception ) {
			return self::error(
				'invalid_' . $field,
				sprintf(
					/* translators: %s: quote timestamp field name. */
					__( '%s must be a valid timestamp.', 'alborsa-platform' ),
					$field
				)
			);
		}

		return $date->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
	}

	private static function non_negative_integer( mixed $value ): ?int {
		if ( is_int( $value ) ) {
			return $value >= 0 ? $value : null;
		}

		if ( ! is_string( $value ) || ! preg_match( '/\A\d+\z/', $value ) ) {
			return null;
		}

		$integer = filter_var( $value, FILTER_VALIDATE_INT );

		return false !== $integer && $integer >= 0 ? $integer : null;
	}

	/** @param array<string, int|string|null> $values Normalized values. */
	private static function hash( array $values ): string {
		$encoded = wp_json_encode( $values, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		return hash( 'sha256', false === $encoded ? '' : $encoded );
	}

	private static function scalar_string( mixed $value ): string {
		return is_scalar( $value ) ? (string) $value : '';
	}

	private static function error( string $suffix, string $message ): WP_Error {
		return new WP_Error( 'alborsa_platform_quote_' . $suffix, $message );
	}
}

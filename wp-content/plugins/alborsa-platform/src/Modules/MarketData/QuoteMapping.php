<?php
/**
 * Validated asset-to-provider quote mapping.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\MarketData;

defined( 'ABSPATH' ) || exit;

final class QuoteMapping {
	/**
	 * @param array{provider_key: string, provider_symbol: string, delay_seconds: int, attribution_label: string, attribution_url: string} $values Validated mapping values.
	 */
	private function __construct( private readonly array $values ) {
	}

	/**
	 * @param array<string, mixed> $mapping Stored provider mapping.
	 */
	public static function from_array( array $mapping ): ?self {
		$provider_key    = self::scalar_string( $mapping['provider_key'] ?? '' );
		$provider_symbol = trim( self::scalar_string( $mapping['provider_symbol'] ?? '' ) );
		$capabilities    = self::capabilities( $mapping['data_capabilities'] ?? array() );
		$enabled         = true === ( $mapping['enabled'] ?? false );

		if ( ! $enabled
			|| ! preg_match( '/\A[a-z0-9_-]{1,32}\z/', $provider_key )
			|| '' === $provider_symbol
			|| strlen( $provider_symbol ) > 64
			|| ! in_array( 'quotes', $capabilities, true )
		) {
			return null;
		}

		$delay_seconds = $mapping['data_delay_seconds'] ?? 0;

		return new self(
			array(
				'provider_key'      => $provider_key,
				'provider_symbol'   => $provider_symbol,
				'delay_seconds'     => is_numeric( $delay_seconds ) ? max( 0, (int) $delay_seconds ) : 0,
				'attribution_label' => trim( self::scalar_string( $mapping['attribution_label'] ?? '' ) ),
				'attribution_url'   => self::http_url( $mapping['attribution_url'] ?? '' ),
			)
		);
	}

	public function provider_key(): string {
		return $this->values['provider_key'];
	}

	public function provider_symbol(): string {
		return $this->values['provider_symbol'];
	}

	public function delay_seconds(): int {
		return $this->values['delay_seconds'];
	}

	public function attribution_label(): string {
		return $this->values['attribution_label'];
	}

	public function attribution_url(): string {
		return $this->values['attribution_url'];
	}

	/**
	 * @return list<string>
	 */
	private static function capabilities( mixed $capabilities ): array {
		if ( ! is_array( $capabilities ) ) {
			return array();
		}

		return array_values(
			array_filter(
				$capabilities,
				static fn( mixed $capability ): bool => is_string( $capability ) && '' !== $capability
			)
		);
	}

	private static function scalar_string( mixed $value ): string {
		return is_scalar( $value ) ? (string) $value : '';
	}

	private static function http_url( mixed $value ): string {
		$url    = trim( self::scalar_string( $value ) );
		$scheme = wp_parse_url( $url, PHP_URL_SCHEME );

		return in_array( $scheme, array( 'http', 'https' ), true ) ? $url : '';
	}
}

<?php
/**
 * Asset metadata registration and normalization.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\Assets;

defined( 'ABSPATH' ) || exit;

final class AssetMeta {
	public const CANONICAL_SYMBOL  = 'alb_canonical_symbol';
	public const BASE_CURRENCY     = 'alb_base_currency';
	public const QUOTE_CURRENCY    = 'alb_quote_currency';
	public const ISIN              = 'alb_isin';
	public const OFFICIAL_WEBSITE  = 'alb_official_website';
	public const LISTING_STATUS    = 'alb_listing_status';
	public const PROVIDER_MAPPINGS = '_alb_provider_mappings';

	public function register(): void {
		$this->register_string( self::CANONICAL_SYMBOL, array( $this, 'sanitize_symbol' ) );
		$this->register_string( self::BASE_CURRENCY, array( $this, 'sanitize_currency' ) );
		$this->register_string( self::QUOTE_CURRENCY, array( $this, 'sanitize_currency' ) );
		$this->register_string( self::ISIN, array( $this, 'sanitize_isin' ) );
		$this->register_string( self::OFFICIAL_WEBSITE, array( $this, 'sanitize_http_url' ) );
		$this->register_string( self::LISTING_STATUS, array( $this, 'sanitize_listing_status' ) );
		$this->register_provider_mappings();
	}

	public function can_edit( bool $allowed, string $meta_key, int $post_id ): bool {
		unset( $allowed, $meta_key );

		return current_user_can( 'edit_post', $post_id );
	}

	public function sanitize_symbol( mixed $symbol ): string {
		$normalized_symbol = strtoupper( sanitize_text_field( $this->scalar_string( $symbol ) ) );

		return preg_match( '~\A[A-Z0-9.^=_:/-]{1,32}\z~', $normalized_symbol ) ? $normalized_symbol : '';
	}

	public function sanitize_currency( mixed $currency ): string {
		$normalized_currency = strtoupper( sanitize_text_field( $this->scalar_string( $currency ) ) );

		return preg_match( '/\A[A-Z]{3}\z/', $normalized_currency ) ? $normalized_currency : '';
	}

	public function sanitize_isin( mixed $isin ): string {
		$normalized_isin = strtoupper( sanitize_text_field( $this->scalar_string( $isin ) ) );

		return preg_match( '/\A[A-Z]{2}[A-Z0-9]{9}[0-9]\z/', $normalized_isin ) ? $normalized_isin : '';
	}

	public function sanitize_http_url( mixed $url ): string {
		return esc_url_raw( $this->scalar_string( $url ), array( 'http', 'https' ) );
	}

	public function sanitize_listing_status( mixed $status ): string {
		$normalized_status = sanitize_key( $this->scalar_string( $status ) );

		return strlen( $normalized_status ) <= 32 ? $normalized_status : '';
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public function sanitize_provider_mappings( mixed $mappings ): array {
		if ( ! is_array( $mappings ) ) {
			return array();
		}

		$sanitized_mappings = array();

		foreach ( $mappings as $mapping ) {
			$sanitized_mapping = $this->sanitize_provider_mapping( $mapping );

			if ( null !== $sanitized_mapping ) {
				$sanitized_mappings[] = $sanitized_mapping;
			}
		}

		return $sanitized_mappings;
	}

	/**
	 * @param callable(mixed): string $sanitize_callback Field sanitizer.
	 */
	private function register_string( string $meta_key, callable $sanitize_callback ): void {
		register_post_meta(
			AssetsModule::POST_TYPE,
			$meta_key,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => $sanitize_callback,
				'auth_callback'     => array( $this, 'can_edit' ),
			)
		);
	}

	private function register_provider_mappings(): void {
		register_post_meta(
			AssetsModule::POST_TYPE,
			self::PROVIDER_MAPPINGS,
			array(
				'type'              => 'array',
				'single'            => true,
				'show_in_rest'      => false,
				'sanitize_callback' => array( $this, 'sanitize_provider_mappings' ),
				'auth_callback'     => array( $this, 'can_edit' ),
			)
		);
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private function sanitize_provider_mapping( mixed $mapping ): ?array {
		if ( ! is_array( $mapping ) ) {
			return null;
		}

		$provider_key    = sanitize_key( $this->scalar_string( $mapping['provider_key'] ?? '' ) );
		$provider_symbol = sanitize_text_field( $this->scalar_string( $mapping['provider_symbol'] ?? '' ) );

		if ( '' === $provider_key || '' === $provider_symbol || strlen( $provider_symbol ) > 64 ) {
			return null;
		}

		return array(
			'provider_key'       => $provider_key,
			'provider_symbol'    => $provider_symbol,
			'data_capabilities'  => $this->sanitize_capabilities( $mapping['data_capabilities'] ?? array() ),
			'data_delay_seconds' => $this->non_negative_int( $mapping['data_delay_seconds'] ?? 0 ),
			'attribution_label'  => sanitize_text_field( $this->scalar_string( $mapping['attribution_label'] ?? '' ) ),
			'attribution_url'    => $this->sanitize_http_url( $mapping['attribution_url'] ?? '' ),
			'enabled'            => $this->sanitize_boolean( $mapping['enabled'] ?? false ),
		);
	}

	/**
	 * @return list<string>
	 */
	private function sanitize_capabilities( mixed $capabilities ): array {
		if ( ! is_array( $capabilities ) ) {
			return array();
		}

		$sanitized_capabilities = array();

		foreach ( $capabilities as $capability ) {
			$sanitized_capability = sanitize_key( $this->scalar_string( $capability ) );

			if ( '' !== $sanitized_capability ) {
				$sanitized_capabilities[] = $sanitized_capability;
			}
		}

		return array_values( array_unique( $sanitized_capabilities ) );
	}

	private function scalar_string( mixed $value ): string {
		return is_scalar( $value ) ? (string) $value : '';
	}

	private function non_negative_int( mixed $value ): int {
		return is_numeric( $value ) ? max( 0, (int) $value ) : 0;
	}

	private function sanitize_boolean( mixed $value ): bool {
		return is_bool( $value ) || is_int( $value ) || is_string( $value )
			? rest_sanitize_boolean( $value )
			: false;
	}
}

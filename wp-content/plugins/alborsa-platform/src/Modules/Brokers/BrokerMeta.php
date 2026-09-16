<?php
/**
 * Broker metadata registration and validation.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\Brokers;

defined( 'ABSPATH' ) || exit;

final class BrokerMeta {
	public const LEGAL_NAME         = 'alb_broker_legal_name';
	public const OFFICIAL_WEBSITE   = 'alb_broker_official_website';
	public const SERVICES           = 'alb_broker_services';
	public const PLATFORMS          = 'alb_broker_platforms';
	public const CURRENCIES         = 'alb_broker_currencies';
	public const DEPOSIT_METHODS    = 'alb_broker_deposit_methods';
	public const ARABIC_SUPPORT     = 'alb_broker_arabic_support';
	public const MINIMUM_DEPOSIT    = 'alb_broker_minimum_deposit';
	public const DEPOSIT_CURRENCY   = 'alb_broker_deposit_currency';
	public const ACCOUNT_TYPES      = 'alb_broker_account_types';
	public const FEE_SUMMARY        = 'alb_broker_fee_summary';
	public const PROS               = 'alb_broker_pros';
	public const CONS               = 'alb_broker_cons';
	public const LICENSES           = '_alb_broker_licenses';
	public const LICENSE_UNVERIFIED = 'unverified';
	public const LICENSE_VERIFIED   = 'verified';
	public const LICENSE_EXPIRED    = 'expired';
	public const LICENSE_REVOKED    = 'revoked';

	private const LICENSE_STATUSES = array(
		self::LICENSE_UNVERIFIED,
		self::LICENSE_VERIFIED,
		self::LICENSE_EXPIRED,
		self::LICENSE_REVOKED,
	);

	private const LIST_FIELDS = array(
		self::SERVICES,
		self::PLATFORMS,
		self::CURRENCIES,
		self::DEPOSIT_METHODS,
		self::ACCOUNT_TYPES,
		self::PROS,
		self::CONS,
	);

	public function register(): void {
		$this->register_string( self::LEGAL_NAME, array( $this, 'sanitize_text' ) );
		$this->register_string( self::OFFICIAL_WEBSITE, array( $this, 'sanitize_http_url' ) );
		$this->register_string( self::MINIMUM_DEPOSIT, array( $this, 'sanitize_decimal' ) );
		$this->register_string( self::DEPOSIT_CURRENCY, array( $this, 'sanitize_currency' ) );
		$this->register_string( self::FEE_SUMMARY, array( $this, 'sanitize_textarea' ) );
		$this->register_boolean( self::ARABIC_SUPPORT );

		foreach ( self::LIST_FIELDS as $meta_key ) {
			$this->register_list( $meta_key );
		}

		$this->register_licenses();
	}

	public function can_edit( bool $allowed, string $meta_key, int $post_id ): bool {
		unset( $allowed, $meta_key );

		return current_user_can( 'edit_post', $post_id );
	}

	public function sanitize_text( mixed $text ): string {
		return sanitize_text_field( $this->scalar_string( $text ) );
	}

	public function sanitize_textarea( mixed $text ): string {
		return sanitize_textarea_field( $this->scalar_string( $text ) );
	}

	public function sanitize_http_url( mixed $url ): string {
		return esc_url_raw( $this->scalar_string( $url ), array( 'http', 'https' ) );
	}

	public function sanitize_decimal( mixed $decimal ): string {
		$decimal = trim( $this->scalar_string( $decimal ) );

		if ( '' === $decimal ) {
			return '';
		}

		return preg_match( '/\A\d{1,12}(?:\.\d{1,2})?\z/', $decimal ) ? $decimal : '';
	}

	public function sanitize_currency( mixed $currency ): string {
		$currency = strtoupper( trim( $this->scalar_string( $currency ) ) );

		return preg_match( '/\A[A-Z]{3}\z/', $currency ) ? $currency : '';
	}

	public function sanitize_boolean( mixed $value ): bool {
		return is_bool( $value ) || is_int( $value ) || is_string( $value )
			? rest_sanitize_boolean( $value )
			: false;
	}

	/** @return list<string> */
	public function sanitize_list( mixed $values ): array {
		if ( ! is_array( $values ) ) {
			return array();
		}

		$sanitized = array_map(
			fn( mixed $value ): string => $this->sanitize_text( $value ),
			$values
		);

		return array_values( array_unique( array_filter( $sanitized ) ) );
	}

	/** @return list<array<string, string>> */
	public function sanitize_licenses( mixed $licenses ): array {
		if ( ! is_array( $licenses ) ) {
			return array();
		}

		$sanitized = array();

		foreach ( array_slice( $licenses, 0, 20 ) as $license ) {
			$license = $this->sanitize_license( $license );

			if ( null !== $license ) {
				$sanitized[] = $license;
			}
		}

		return $sanitized;
	}

	/** @param callable(mixed): mixed $sanitizer Meta sanitizer. */
	private function register_string( string $meta_key, callable $sanitizer ): void {
		register_post_meta(
			BrokersModule::POST_TYPE,
			$meta_key,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => $sanitizer,
				'auth_callback'     => array( $this, 'can_edit' ),
			)
		);
	}

	private function register_boolean( string $meta_key ): void {
		register_post_meta(
			BrokersModule::POST_TYPE,
			$meta_key,
			array(
				'type'              => 'boolean',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => array( $this, 'sanitize_boolean' ),
				'auth_callback'     => array( $this, 'can_edit' ),
			)
		);
	}

	private function register_list( string $meta_key ): void {
		register_post_meta(
			BrokersModule::POST_TYPE,
			$meta_key,
			array(
				'type'              => 'array',
				'single'            => true,
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'string' ),
					),
				),
				'sanitize_callback' => array( $this, 'sanitize_list' ),
				'auth_callback'     => array( $this, 'can_edit' ),
			)
		);
	}

	private function register_licenses(): void {
		register_post_meta(
			BrokersModule::POST_TYPE,
			self::LICENSES,
			array(
				'type'              => 'array',
				'single'            => true,
				'show_in_rest'      => false,
				'sanitize_callback' => array( $this, 'sanitize_licenses' ),
				'auth_callback'     => array( $this, 'can_edit' ),
			)
		);
	}

	/** @return array<string, string>|null */
	private function sanitize_license( mixed $license ): ?array {
		if ( ! is_array( $license ) ) {
			return null;
		}

		$status = sanitize_key( $this->scalar_string( $license['verification_status'] ?? '' ) );
		$status = in_array( $status, self::LICENSE_STATUSES, true ) ? $status : self::LICENSE_UNVERIFIED;
		$record = array(
			'regulator'           => $this->sanitize_text( $license['regulator'] ?? '' ),
			'country'             => $this->sanitize_text( $license['country'] ?? '' ),
			'legal_entity'        => $this->sanitize_text( $license['legal_entity'] ?? '' ),
			'license_number'      => $this->sanitize_text( $license['license_number'] ?? '' ),
			'verification_status' => $status,
			'source_url'          => $this->sanitize_http_url( $license['source_url'] ?? '' ),
			'verified_at'         => $this->sanitize_date( $license['verified_at'] ?? '' ),
			'internal_notes'      => $this->sanitize_textarea( $license['internal_notes'] ?? '' ),
		);

		return '' === $record['regulator'] && '' === $record['license_number'] ? null : $record;
	}

	private function sanitize_date( mixed $date ): string {
		$date = trim( $this->scalar_string( $date ) );

		if ( ! preg_match( '/\A(\d{4})-(\d{2})-(\d{2})\z/', $date, $parts ) ) {
			return '';
		}

		return checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] ) ? $date : '';
	}

	private function scalar_string( mixed $value ): string {
		return is_scalar( $value ) ? (string) $value : '';
	}
}

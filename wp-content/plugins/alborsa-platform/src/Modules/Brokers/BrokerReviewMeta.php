<?php
/**
 * Protected metadata for moderated broker reviews.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\Brokers;

defined( 'ABSPATH' ) || exit;

final class BrokerReviewMeta {
	public const COMMENT_TYPE        = 'alb_broker_review';
	public const RATING              = '_alb_broker_review_rating';
	public const VERIFICATION_STATUS = '_alb_broker_review_verification_status';
	public const EXPERIENCE_TYPE     = '_alb_broker_review_experience_type';
	public const EXPERIENCE_DATE     = '_alb_broker_review_experience_date';
	public const MODERATION_NOTE     = '_alb_broker_review_moderation_note';
	public const CONSENT_VERSION     = '_alb_broker_review_consent_version';
	public const UNVERIFIED          = 'unverified';
	public const EMAIL_VERIFIED      = 'email_verified';
	public const MANUALLY_VERIFIED   = 'manually_verified';
	public const REJECTED            = 'rejected';

	private const VERIFICATION_STATUSES = array(
		self::UNVERIFIED,
		self::EMAIL_VERIFIED,
		self::MANUALLY_VERIFIED,
		self::REJECTED,
	);

	public function register(): void {
		$this->register_integer( self::RATING, array( $this, 'sanitize_rating' ) );

		$this->register_string( self::VERIFICATION_STATUS, array( $this, 'sanitize_verification_status' ) );
		$this->register_string( self::EXPERIENCE_TYPE, array( $this, 'sanitize_key_value' ) );
		$this->register_string( self::EXPERIENCE_DATE, array( $this, 'sanitize_date' ) );
		$this->register_string( self::MODERATION_NOTE, array( $this, 'sanitize_textarea' ) );
		$this->register_string( self::CONSENT_VERSION, array( $this, 'sanitize_key_value' ) );
	}

	public function can_moderate( bool $allowed ): bool {
		unset( $allowed );

		return current_user_can( 'moderate_comments' );
	}

	public function sanitize_rating( mixed $rating ): int {
		$rating = is_numeric( $rating ) ? (int) $rating : 0;

		return $rating >= 1 && $rating <= 5 ? $rating : 0;
	}

	public function sanitize_text( mixed $text ): string {
		return is_scalar( $text ) ? sanitize_text_field( (string) $text ) : '';
	}

	public function sanitize_textarea( mixed $text ): string {
		return is_scalar( $text ) ? sanitize_textarea_field( (string) $text ) : '';
	}

	public function sanitize_key_value( mixed $value ): string {
		return is_scalar( $value ) ? sanitize_key( (string) $value ) : '';
	}

	public function sanitize_verification_status( mixed $status ): string {
		$status = $this->sanitize_key_value( $status );

		return in_array( $status, self::VERIFICATION_STATUSES, true ) ? $status : self::UNVERIFIED;
	}

	public function sanitize_date( mixed $date ): string {
		$date = is_scalar( $date ) ? (string) $date : '';

		if ( ! preg_match( '/\A(\d{4})-(\d{2})-(\d{2})\z/', $date, $parts ) ) {
			return '';
		}

		return checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] ) ? $date : '';
	}

	/** @param callable(mixed): mixed $sanitizer Meta sanitizer. */
	private function register_integer( string $meta_key, callable $sanitizer ): void {
		register_meta(
			'comment',
			$meta_key,
			array(
				'type'              => 'integer',
				'single'            => true,
				'show_in_rest'      => false,
				'sanitize_callback' => $sanitizer,
				'auth_callback'     => array( $this, 'can_moderate' ),
			)
		);
	}

	/** @param callable(mixed): mixed $sanitizer Meta sanitizer. */
	private function register_string( string $meta_key, callable $sanitizer ): void {
		register_meta(
			'comment',
			$meta_key,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => false,
				'sanitize_callback' => $sanitizer,
				'auth_callback'     => array( $this, 'can_moderate' ),
			)
		);
	}
}

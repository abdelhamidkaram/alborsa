<?php
/**
 * Editorial provenance and review metadata.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\Editorial;

defined( 'ABSPATH' ) || exit;

final class EditorialMeta {
	public const SOURCE_NAME      = 'alb_source_name';
	public const SOURCE_URL       = 'alb_source_url';
	public const REVIEWER_ID      = 'alb_reviewer_id';
	public const LAST_REVIEWED_AT = 'alb_last_reviewed_at';
	public const DISCLOSURE       = 'alb_editorial_disclosure';
	public const INVESTOR_NOTE    = 'alb_investor_note';
	public const RSS_ITEM_HASH    = '_alb_rss_item_hash';

	private const POST_TYPES = array(
		'post',
		EditorialModule::ANALYSIS_POST_TYPE,
		EditorialModule::GUIDE_POST_TYPE,
	);

	public function register(): void {
		$this->register_string( 'post', self::INVESTOR_NOTE, array( $this, 'sanitize_textarea' ), false );

		foreach ( self::POST_TYPES as $post_type ) {
			$this->register_string( $post_type, self::SOURCE_NAME, array( $this, 'sanitize_text' ), true );
			$this->register_string( $post_type, self::SOURCE_URL, array( $this, 'sanitize_http_url' ), true );
			$this->register_integer( $post_type, self::REVIEWER_ID );
			$this->register_string( $post_type, self::LAST_REVIEWED_AT, array( $this, 'sanitize_date' ), true );
			$this->register_string( $post_type, self::DISCLOSURE, array( $this, 'sanitize_textarea' ), true );
			$this->register_string( $post_type, self::RSS_ITEM_HASH, array( $this, 'sanitize_hash' ), false );
		}
	}

	public function can_edit( bool $allowed, string $meta_key, int $post_id ): bool {
		unset( $allowed, $meta_key );

		return current_user_can( 'edit_post', $post_id );
	}

	public function sanitize_text( mixed $value ): string {
		return sanitize_text_field( $this->scalar_string( $value ) );
	}

	public function sanitize_textarea( mixed $value ): string {
		return sanitize_textarea_field( $this->scalar_string( $value ) );
	}

	public function sanitize_http_url( mixed $value ): string {
		return esc_url_raw( $this->scalar_string( $value ), array( 'http', 'https' ) );
	}

	public function sanitize_reviewer_id( mixed $value ): int {
		return is_numeric( $value ) ? max( 0, (int) $value ) : 0;
	}

	public function sanitize_date( mixed $value ): string {
		$date = trim( $this->scalar_string( $value ) );

		if ( ! preg_match( '/\A(\d{4})-(\d{2})-(\d{2})\z/', $date, $matches ) ) {
			return '';
		}

		return checkdate( (int) $matches[2], (int) $matches[3], (int) $matches[1] ) ? $date : '';
	}

	public function sanitize_hash( mixed $value ): string {
		$hash = strtolower( trim( $this->scalar_string( $value ) ) );

		return preg_match( '/\A[a-f0-9]{64}\z/', $hash ) ? $hash : '';
	}

	/** @param callable(mixed): mixed $sanitizer Meta sanitizer. */
	private function register_string( string $post_type, string $meta_key, callable $sanitizer, bool $show_in_rest ): void {
		register_post_meta(
			$post_type,
			$meta_key,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => $show_in_rest,
				'sanitize_callback' => $sanitizer,
				'auth_callback'     => array( $this, 'can_edit' ),
			)
		);
	}

	private function register_integer( string $post_type, string $meta_key ): void {
		register_post_meta(
			$post_type,
			$meta_key,
			array(
				'type'              => 'integer',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => array( $this, 'sanitize_reviewer_id' ),
				'auth_callback'     => array( $this, 'can_edit' ),
			)
		);
	}

	private function scalar_string( mixed $value ): string {
		return is_scalar( $value ) ? (string) $value : '';
	}
}

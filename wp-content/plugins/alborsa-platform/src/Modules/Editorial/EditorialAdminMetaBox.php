<?php
/**
 * Editorial provenance controls in the WordPress editor.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\Editorial;

use Alborsa\Platform\Foundation\AuditLog;
use Alborsa\Platform\Foundation\RoleCapabilities;

defined( 'ABSPATH' ) || exit;

final class EditorialAdminMetaBox {
	private const NONCE_ACTION = 'alborsa_platform_save_editorial_meta';
	private const NONCE_NAME   = 'alborsa_platform_editorial_nonce';
	private const FORM_KEY     = 'alborsa_editorial';
	private const POST_TYPES   = array( 'post', EditorialModule::ANALYSIS_POST_TYPE, EditorialModule::GUIDE_POST_TYPE );

	private const INVESTOR_NONCE_ACTION = 'alborsa_platform_save_investor_note';
	private const INVESTOR_NONCE_NAME   = 'alborsa_platform_investor_note_nonce';

	public function __construct( private EditorialMeta $meta ) {
	}

	public function register(): void {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );

		foreach ( self::POST_TYPES as $post_type ) {
			add_action( 'save_post_' . $post_type, array( $this, 'save' ), 10, 2 );
		}

		add_action( 'save_post_post', array( $this, 'save_investor_note' ) );
	}

	public function add_meta_boxes(): void {
		foreach ( self::POST_TYPES as $post_type ) {
			add_meta_box(
				'alborsa-editorial-provenance',
				__( 'Source and editorial review', 'alborsa-platform' ),
				array( $this, 'render' ),
				$post_type,
				'side',
				'default'
			);
		}

		add_meta_box(
			'alborsa-investor-note',
			__( 'ماذا يعني هذا للمستثمر؟', 'alborsa-platform' ),
			array( $this, 'render_investor_note' ),
			'post',
			'normal',
			'default'
		);
	}

	public function render( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		$this->render_input( $post, EditorialMeta::SOURCE_NAME, __( 'Source name', 'alborsa-platform' ), 'text' );
		$this->render_input( $post, EditorialMeta::SOURCE_URL, __( 'Source URL', 'alborsa-platform' ), 'url' );
		$this->render_input( $post, EditorialMeta::REVIEWER_ID, __( 'Reviewer user ID', 'alborsa-platform' ), 'number' );
		$this->render_input( $post, EditorialMeta::LAST_REVIEWED_AT, __( 'Last reviewed (YYYY-MM-DD)', 'alborsa-platform' ), 'date' );

		$this->render_textarea( $post, EditorialMeta::DISCLOSURE, __( 'Disclosure', 'alborsa-platform' ), self::FORM_KEY . '[' . EditorialMeta::DISCLOSURE . ']' );
	}

	public function render_investor_note( \WP_Post $post ): void {
		wp_nonce_field( self::INVESTOR_NONCE_ACTION, self::INVESTOR_NONCE_NAME, false );
		$this->render_textarea( $post, EditorialMeta::INVESTOR_NOTE, __( 'نص القسم', 'alborsa-platform' ), EditorialMeta::INVESTOR_NOTE );
	}

	public function save_investor_note( int $post_id ): void {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$nonce = isset( $_POST[ self::INVESTOR_NONCE_NAME ] ) && is_string( $_POST[ self::INVESTOR_NONCE_NAME ] )
			? sanitize_text_field( wp_unslash( $_POST[ self::INVESTOR_NONCE_NAME ] ) )
			: '';

		if ( ! wp_verify_nonce( $nonce, self::INVESTOR_NONCE_ACTION )
			|| ! isset( $_POST[ EditorialMeta::INVESTOR_NOTE ] )
			|| ! is_string( $_POST[ EditorialMeta::INVESTOR_NOTE ] ) ) {
			return;
		}

		$investor_note = sanitize_textarea_field( wp_unslash( $_POST[ EditorialMeta::INVESTOR_NOTE ] ) );
		$changed       = '' === $investor_note
			? delete_post_meta( $post_id, EditorialMeta::INVESTOR_NOTE )
			: update_post_meta( $post_id, EditorialMeta::INVESTOR_NOTE, wp_slash( $investor_note ) );

		if ( $changed ) {
			( new AuditLog() )->record( 'investor_note_updated', 'post', $post_id );
		}
	}

	public function save( int $post_id, \WP_Post $post ): void {
		if ( ! $this->can_save( $post_id, $post ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- can_save() verifies the nonce; every field is sanitized below.
		$payload = wp_unslash( $_POST[ self::FORM_KEY ] );

		if ( ! is_array( $payload ) ) {
			return;
		}

		$values = array(
			EditorialMeta::SOURCE_NAME      => $this->meta->sanitize_text( $payload[ EditorialMeta::SOURCE_NAME ] ?? '' ),
			EditorialMeta::SOURCE_URL       => $this->meta->sanitize_http_url( $payload[ EditorialMeta::SOURCE_URL ] ?? '' ),
			EditorialMeta::REVIEWER_ID      => $this->valid_reviewer_id( $payload[ EditorialMeta::REVIEWER_ID ] ?? 0 ),
			EditorialMeta::LAST_REVIEWED_AT => $this->meta->sanitize_date( $payload[ EditorialMeta::LAST_REVIEWED_AT ] ?? '' ),
			EditorialMeta::DISCLOSURE       => $this->meta->sanitize_textarea( $payload[ EditorialMeta::DISCLOSURE ] ?? '' ),
		);

		foreach ( $values as $meta_key => $value ) {
			if ( '' === $value || 0 === $value ) {
				delete_post_meta( $post_id, $meta_key );
				continue;
			}

			update_post_meta( $post_id, $meta_key, $value );
		}

		( new AuditLog() )->record( 'editorial_metadata_updated', $post->post_type, $post_id );
	}

	private function render_input( \WP_Post $post, string $meta_key, string $label, string $type ): void {
		$value = get_post_meta( $post->ID, $meta_key, true );
		$value = is_scalar( $value ) ? (string) $value : '';
		printf(
			'<p><label for="%1$s"><strong>%2$s</strong></label><input class="widefat" id="%1$s" name="%3$s[%1$s]" type="%4$s" value="%5$s"></p>',
			esc_attr( $meta_key ),
			esc_html( $label ),
			esc_attr( self::FORM_KEY ),
			esc_attr( $type ),
			esc_attr( $value )
		);
	}

	private function render_textarea( \WP_Post $post, string $meta_key, string $label, string $field_name ): void {
		$value = get_post_meta( $post->ID, $meta_key, true );
		$value = is_scalar( $value ) ? (string) $value : '';
		printf(
			'<p><label for="%1$s"><strong>%2$s</strong></label><textarea class="widefat" id="%1$s" name="%3$s" rows="4">%4$s</textarea></p>',
			esc_attr( $meta_key ),
			esc_html( $label ),
			esc_attr( $field_name ),
			esc_textarea( $value )
		);
	}

	private function can_save( int $post_id, \WP_Post $post ): bool {
		if ( ! in_array( $post->post_type, self::POST_TYPES, true )
			|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			|| ! current_user_can( 'edit_post', $post_id )
			|| ! isset( $_POST[ self::NONCE_NAME ], $_POST[ self::FORM_KEY ] )
		) {
			return false;
		}

		$nonce = sanitize_text_field( wp_unslash( (string) $_POST[ self::NONCE_NAME ] ) );

		return (bool) wp_verify_nonce( $nonce, self::NONCE_ACTION );
	}

	private function valid_reviewer_id( mixed $value ): int {
		$reviewer_id = $this->meta->sanitize_reviewer_id( $value );
		$reviewer    = $reviewer_id > 0 ? get_userdata( $reviewer_id ) : false;

		return $reviewer instanceof \WP_User
			&& ( user_can( $reviewer, 'edit_posts' ) || user_can( $reviewer, RoleCapabilities::EDIT_CONTENT ) )
			? $reviewer_id
			: 0;
	}
}

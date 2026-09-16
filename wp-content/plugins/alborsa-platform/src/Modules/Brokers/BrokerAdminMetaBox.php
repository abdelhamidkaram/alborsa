<?php
/**
 * Broker profile and license editing controls.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\Brokers;

use Alborsa\Platform\Foundation\AuditLog;

defined( 'ABSPATH' ) || exit;

final class BrokerAdminMetaBox {
	private const NONCE_ACTION = 'alborsa_platform_save_broker_profile';
	private const NONCE_NAME   = 'alborsa_platform_broker_nonce';
	private const FORM_KEY     = 'alborsa_broker';

	public function __construct( private BrokerMeta $meta ) {
	}

	public function register(): void {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_' . BrokersModule::POST_TYPE, array( $this, 'save' ), 10, 2 );
	}

	public function add_meta_box(): void {
		add_meta_box(
			'alborsa-broker-profile',
			__( 'Broker profile and licenses', 'alborsa-platform' ),
			array( $this, 'render' ),
			BrokersModule::POST_TYPE,
			'normal',
			'default'
		);
	}

	public function render( \WP_Post $broker ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		foreach ( $this->text_fields() as $meta_key => $label ) {
			$this->render_text_field( $broker, $meta_key, $label );
		}

		foreach ( $this->list_fields() as $meta_key => $label ) {
			$this->render_list_field( $broker, $meta_key, $label );
		}

		$this->render_textarea( $broker, BrokerMeta::FEE_SUMMARY, __( 'Fee summary', 'alborsa-platform' ) );
		$this->render_checkbox( $broker );
		$this->render_licenses( $broker );
	}

	public function save( int $broker_id, \WP_Post $broker ): void {
		if ( ! $this->can_save( $broker_id, $broker ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- can_save() verifies the nonce and capability; each field is sanitized below.
		$payload = wp_unslash( $_POST[ self::FORM_KEY ] );

		if ( ! is_array( $payload ) ) {
			return;
		}

		$this->save_text_fields( $broker_id, $payload );
		$this->save_list_fields( $broker_id, $payload );
		$this->save_boolean( $broker_id, $payload );
		$this->save_licenses( $broker_id, $payload );
		( new AuditLog() )->record( 'broker_profile_updated', $broker->post_type, $broker_id );
	}

	/** @return array<string, string> */
	private function text_fields(): array {
		return array(
			BrokerMeta::LEGAL_NAME       => __( 'Legal name', 'alborsa-platform' ),
			BrokerMeta::OFFICIAL_WEBSITE => __( 'Official website', 'alborsa-platform' ),
			BrokerMeta::MINIMUM_DEPOSIT  => __( 'Minimum deposit', 'alborsa-platform' ),
			BrokerMeta::DEPOSIT_CURRENCY => __( 'Deposit currency', 'alborsa-platform' ),
		);
	}

	/** @return array<string, string> */
	private function list_fields(): array {
		return array(
			BrokerMeta::SERVICES        => __( 'Services', 'alborsa-platform' ),
			BrokerMeta::PLATFORMS       => __( 'Platforms', 'alborsa-platform' ),
			BrokerMeta::CURRENCIES      => __( 'Supported currencies', 'alborsa-platform' ),
			BrokerMeta::DEPOSIT_METHODS => __( 'Deposit and withdrawal methods', 'alborsa-platform' ),
			BrokerMeta::ACCOUNT_TYPES   => __( 'Account types', 'alborsa-platform' ),
			BrokerMeta::PROS            => __( 'Pros', 'alborsa-platform' ),
			BrokerMeta::CONS            => __( 'Cons', 'alborsa-platform' ),
		);
	}

	private function render_text_field( \WP_Post $broker, string $meta_key, string $label ): void {
		$value = get_post_meta( $broker->ID, $meta_key, true );
		$value = is_scalar( $value ) ? (string) $value : '';

		printf(
			'<p><label for="%1$s"><strong>%2$s</strong></label><br><input class="widefat" id="%1$s" name="%3$s[%1$s]" type="text" value="%4$s"></p>',
			esc_attr( $meta_key ),
			esc_html( $label ),
			esc_attr( self::FORM_KEY ),
			esc_attr( $value )
		);
	}

	private function render_list_field( \WP_Post $broker, string $meta_key, string $label ): void {
		$values = $this->meta->sanitize_list( get_post_meta( $broker->ID, $meta_key, true ) );

		printf(
			'<p><label for="%1$s"><strong>%2$s</strong></label><br><input class="widefat" id="%1$s" name="%3$s[%1$s]" type="text" value="%4$s"><small>%5$s</small></p>',
			esc_attr( $meta_key ),
			esc_html( $label ),
			esc_attr( self::FORM_KEY ),
			esc_attr( implode( ', ', $values ) ),
			esc_html__( 'Separate values with commas.', 'alborsa-platform' )
		);
	}

	private function render_textarea( \WP_Post $broker, string $meta_key, string $label ): void {
		$value = get_post_meta( $broker->ID, $meta_key, true );
		$value = is_scalar( $value ) ? (string) $value : '';

		printf(
			'<p><label for="%1$s"><strong>%2$s</strong></label><br><textarea class="widefat" id="%1$s" name="%3$s[%1$s]" rows="4">%4$s</textarea></p>',
			esc_attr( $meta_key ),
			esc_html( $label ),
			esc_attr( self::FORM_KEY ),
			esc_textarea( $value )
		);
	}

	private function render_checkbox( \WP_Post $broker ): void {
		$checked = (bool) get_post_meta( $broker->ID, BrokerMeta::ARABIC_SUPPORT, true );

		printf(
			'<p><label><input name="%1$s[%2$s]" type="checkbox" value="1" %3$s> %4$s</label></p>',
			esc_attr( self::FORM_KEY ),
			esc_attr( BrokerMeta::ARABIC_SUPPORT ),
			checked( $checked, true, false ),
			esc_html__( 'Arabic-language support is available.', 'alborsa-platform' )
		);
	}

	private function render_licenses( \WP_Post $broker ): void {
		$licenses   = $this->meta->sanitize_licenses( get_post_meta( $broker->ID, BrokerMeta::LICENSES, true ) );
		$licenses[] = array();

		echo '<hr><h3>' . esc_html__( 'Regulatory licenses', 'alborsa-platform' ) . '</h3>';
		echo '<p>' . esc_html__( 'A public license badge requires verified status, an official source URL, and a verification date.', 'alborsa-platform' ) . '</p>';

		foreach ( $licenses as $index => $license ) {
			$this->render_license( (int) $index, $license );
		}
	}

	/** @param array<string, string> $license License values. */
	private function render_license( int $index, array $license ): void {
		echo '<fieldset>';
		printf(
			'<legend><strong>%s</strong></legend>',
			esc_html(
				sprintf(
					/* translators: %d: license row number. */
					__( 'License %d', 'alborsa-platform' ),
					$index + 1
				)
			)
		);

		foreach ( $this->license_text_fields() as $field => $label ) {
			$this->render_license_input( $index, $field, $label, $license[ $field ] ?? '' );
		}

		$this->render_license_status( $index, $license['verification_status'] ?? BrokerMeta::LICENSE_UNVERIFIED );
		$this->render_license_input( $index, 'internal_notes', __( 'Internal notes', 'alborsa-platform' ), $license['internal_notes'] ?? '' );
		echo '</fieldset>';
	}

	/** @return array<string, string> */
	private function license_text_fields(): array {
		return array(
			'regulator'      => __( 'Regulator', 'alborsa-platform' ),
			'country'        => __( 'Country', 'alborsa-platform' ),
			'legal_entity'   => __( 'Legal entity', 'alborsa-platform' ),
			'license_number' => __( 'License number', 'alborsa-platform' ),
			'source_url'     => __( 'Official source URL', 'alborsa-platform' ),
			'verified_at'    => __( 'Verified date (YYYY-MM-DD)', 'alborsa-platform' ),
		);
	}

	private function render_license_input( int $index, string $field, string $label, string $value ): void {
		printf(
			'<p><label><strong>%1$s</strong><br><input class="widefat" name="%2$s[licenses][%3$d][%4$s]" type="text" value="%5$s"></label></p>',
			esc_html( $label ),
			esc_attr( self::FORM_KEY ),
			absint( $index ),
			esc_attr( $field ),
			esc_attr( $value )
		);
	}

	private function render_license_status( int $index, string $status ): void {
		echo '<p><label><strong>' . esc_html__( 'Verification status', 'alborsa-platform' ) . '</strong><br>';
		printf( '<select name="%1$s[licenses][%2$d][verification_status]">', esc_attr( self::FORM_KEY ), absint( $index ) );

		foreach ( $this->license_statuses() as $value => $label ) {
			printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $value ), selected( $status, $value, false ), esc_html( $label ) );
		}

		echo '</select></label></p>';
	}

	/** @return array<string, string> */
	private function license_statuses(): array {
		return array(
			BrokerMeta::LICENSE_UNVERIFIED => __( 'Unverified', 'alborsa-platform' ),
			BrokerMeta::LICENSE_VERIFIED   => __( 'Verified against source', 'alborsa-platform' ),
			BrokerMeta::LICENSE_EXPIRED    => __( 'Expired', 'alborsa-platform' ),
			BrokerMeta::LICENSE_REVOKED    => __( 'Revoked', 'alborsa-platform' ),
		);
	}

	private function can_save( int $broker_id, \WP_Post $broker ): bool {
		if ( BrokersModule::POST_TYPE !== $broker->post_type
			|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			|| ! current_user_can( 'edit_post', $broker_id )
			|| ! isset( $_POST[ self::NONCE_NAME ], $_POST[ self::FORM_KEY ] )
		) {
			return false;
		}

		$nonce = sanitize_text_field( wp_unslash( (string) $_POST[ self::NONCE_NAME ] ) );

		return (bool) wp_verify_nonce( $nonce, self::NONCE_ACTION );
	}

	/** @param array<string, mixed> $payload Form values. */
	private function save_text_fields( int $broker_id, array $payload ): void {
		foreach ( array_keys( $this->text_fields() ) as $meta_key ) {
			$raw_value = $payload[ $meta_key ] ?? '';
			$value     = $this->sanitize_text_field( $meta_key, $raw_value );

			$this->store_meta( $broker_id, $meta_key, $value );
		}

		$this->store_meta(
			$broker_id,
			BrokerMeta::FEE_SUMMARY,
			$this->meta->sanitize_textarea( $payload[ BrokerMeta::FEE_SUMMARY ] ?? '' )
		);
	}

	private function sanitize_text_field( string $meta_key, mixed $value ): string {
		return match ( $meta_key ) {
			BrokerMeta::OFFICIAL_WEBSITE => $this->meta->sanitize_http_url( $value ),
			BrokerMeta::MINIMUM_DEPOSIT  => $this->meta->sanitize_decimal( $value ),
			BrokerMeta::DEPOSIT_CURRENCY => $this->meta->sanitize_currency( $value ),
			default                      => $this->meta->sanitize_text( $value ),
		};
	}

	/** @param array<string, mixed> $payload Form values. */
	private function save_list_fields( int $broker_id, array $payload ): void {
		foreach ( array_keys( $this->list_fields() ) as $meta_key ) {
			$raw_value = $payload[ $meta_key ] ?? '';
			$values    = is_scalar( $raw_value ) ? preg_split( '/\s*,\s*/u', (string) $raw_value ) : array();
			$values    = false === $values ? array() : $values;

			$this->store_meta( $broker_id, $meta_key, $this->meta->sanitize_list( $values ) );
		}
	}

	/** @param array<string, mixed> $payload Form values. */
	private function save_boolean( int $broker_id, array $payload ): void {
		update_post_meta(
			$broker_id,
			BrokerMeta::ARABIC_SUPPORT,
			isset( $payload[ BrokerMeta::ARABIC_SUPPORT ] ) ? 1 : 0
		);
	}

	/** @param array<string, mixed> $payload Form values. */
	private function save_licenses( int $broker_id, array $payload ): void {
		$licenses = $this->meta->sanitize_licenses( $payload['licenses'] ?? array() );

		$this->store_meta( $broker_id, BrokerMeta::LICENSES, $licenses );
	}

	private function store_meta( int $broker_id, string $meta_key, mixed $value ): void {
		if ( '' === $value || array() === $value ) {
			delete_post_meta( $broker_id, $meta_key );
			return;
		}

		update_post_meta( $broker_id, $meta_key, $value );
	}
}

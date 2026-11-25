<?php
/**
 * Static page layout control in the WordPress editor.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\Editorial;

defined( 'ABSPATH' ) || exit;

final class StaticPageAdminMetaBox {
	private const NONCE_ACTION = 'alborsa_platform_save_static_page';
	private const NONCE_NAME   = 'alborsa_platform_static_page_nonce';

	public function __construct( private StaticPageMeta $meta ) {
	}

	public function register(): void {
		add_action( 'add_meta_boxes_page', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_page', array( $this, 'save' ), 10, 2 );
	}

	public function add_meta_box(): void {
		add_meta_box(
			'alborsa-static-page-layout',
			__( 'عرض الصفحات الثابتة', 'alborsa-platform' ),
			array( $this, 'render' ),
			'page',
			'side',
			'default'
		);
	}

	public function render( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		?>
		<p>
			<label for="<?php echo esc_attr( StaticPageMeta::ENABLED ); ?>">
				<input
					type="checkbox"
					id="<?php echo esc_attr( StaticPageMeta::ENABLED ); ?>"
					name="<?php echo esc_attr( StaticPageMeta::ENABLED ); ?>"
					value="1"
					<?php checked( $this->meta->is_enabled( $post->ID ) ); ?>
				>
				<?php esc_html_e( 'اعرض هذه الصفحة ضمن الصفحات الثابتة', 'alborsa-platform' ); ?>
			</label>
		</p>
		<p class="description">
			<?php esc_html_e( 'يضيف قائمة الصفحات الثابتة وفهرسًا تلقائيًا من عناوين H2.', 'alborsa-platform' ); ?>
		</p>
		<?php
	}

	public function save( int $post_id, \WP_Post $post ): void {
		if ( 'page' !== $post->post_type || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		$nonce = $this->submitted_nonce();
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( $this->submitted_enabled() ) {
			update_post_meta( $post_id, StaticPageMeta::ENABLED, true );
			return;
		}

		delete_post_meta( $post_id, StaticPageMeta::ENABLED );
	}

	private function submitted_nonce(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Returned for verification by the save callback.
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! is_string( $_POST[ self::NONCE_NAME ] ) ) {
			return '';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Returned for verification by the save callback.
		return sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) );
	}

	private function submitted_enabled(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- The save callback verifies the nonce before calling this method.
		if ( ! isset( $_POST[ StaticPageMeta::ENABLED ] ) || ! is_string( $_POST[ StaticPageMeta::ENABLED ] ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- The save callback verifies the nonce before calling this method.
		$submitted_value = sanitize_text_field( wp_unslash( $_POST[ StaticPageMeta::ENABLED ] ) );

		return $this->meta->sanitize_boolean( $submitted_value );
	}
}

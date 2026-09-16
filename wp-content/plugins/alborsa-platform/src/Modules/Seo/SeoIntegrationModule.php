<?php
/**
 * Yoast-owned SEO and Schema integration.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\Seo;

use Alborsa\Platform\Foundation\Module;
use Alborsa\Platform\Modules\Editorial\EditorialMeta;
use Alborsa\Platform\Modules\Editorial\EditorialModule;

defined( 'ABSPATH' ) || exit;

final class SeoIntegrationModule implements Module {
	public function register(): void {
		add_filter( 'wpseo_schema_article_post_types', array( $this, 'article_post_types' ) );
		add_filter( 'wpseo_schema_article', array( $this, 'article_schema' ) );
		add_filter( 'wpseo_schema_webpage', array( $this, 'webpage_schema' ) );
	}

	/**
	 * @param list<string> $post_types Yoast Article post types.
	 * @return list<string>
	 */
	public function article_post_types( array $post_types ): array {
		$post_types[] = EditorialModule::ANALYSIS_POST_TYPE;
		$post_types[] = EditorialModule::GUIDE_POST_TYPE;

		return array_values( array_unique( $post_types ) );
	}

	/**
	 * @param array<string, mixed> $data Yoast Article graph piece.
	 * @return array<string, mixed>
	 */
	public function article_schema( array $data ): array {
		$post_id    = get_queried_object_id();
		$source_url = $post_id > 0 ? get_post_meta( $post_id, EditorialMeta::SOURCE_URL, true ) : '';
		$source_url = is_scalar( $source_url ) ? esc_url_raw( (string) $source_url, array( 'http', 'https' ) ) : '';

		if ( '' !== $source_url ) {
			$data['citation'] = $source_url;
		}

		return $data;
	}

	/**
	 * @param array<string, mixed> $data Yoast WebPage graph piece.
	 * @return array<string, mixed>
	 */
	public function webpage_schema( array $data ): array {
		$post_id = get_queried_object_id();

		if ( $post_id < 1 ) {
			return $data;
		}

		$reviewer_id = absint( get_post_meta( $post_id, EditorialMeta::REVIEWER_ID, true ) );
		$reviewer    = $reviewer_id > 0 ? get_userdata( $reviewer_id ) : false;

		if ( $reviewer instanceof \WP_User && '' !== trim( $reviewer->display_name ) ) {
			$data['reviewedBy'] = array(
				'@type' => 'Person',
				'name'  => $reviewer->display_name,
			);
		}

		$reviewed_at = get_post_meta( $post_id, EditorialMeta::LAST_REVIEWED_AT, true );
		$reviewed_at = ( new EditorialMeta() )->sanitize_date( $reviewed_at );

		if ( '' !== $reviewed_at ) {
			$data['lastReviewed'] = $reviewed_at;
		}

		return $data;
	}
}

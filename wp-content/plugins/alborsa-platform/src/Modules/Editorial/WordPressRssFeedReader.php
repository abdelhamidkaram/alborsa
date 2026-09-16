<?php
/**
 * Safe WordPress/SimplePie RSS reader.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\Editorial;

use WP_Error;

defined( 'ABSPATH' ) || exit;

final class WordPressRssFeedReader implements RssFeedReader {
	/**
	 * @return list<array{title: string, url: string, content: string}>|WP_Error
	 */
	public function read( string $url, int $limit ): array|WP_Error {
		if ( ! wp_http_validate_url( $url ) ) {
			return $this->error( 'alborsa_platform_rss_invalid_url', __( 'The RSS URL is not a safe public HTTP URL.', 'alborsa-platform' ), 400 );
		}

		if ( ! function_exists( 'fetch_feed' ) ) {
			require_once ABSPATH . WPINC . '/feed.php';
		}

		$feed = fetch_feed( $url );

		if ( $feed instanceof WP_Error ) {
			return $this->error( 'alborsa_platform_rss_fetch_failed', __( 'The RSS feed could not be fetched.', 'alborsa-platform' ), 502 );
		}

		$items = $feed->get_items( 0, $limit );
		$rows  = array();

		foreach ( $items as $item ) {
			$title    = sanitize_text_field( wp_strip_all_tags( (string) $item->get_title() ) );
			$item_url = esc_url_raw( (string) $item->get_permalink(), array( 'http', 'https' ) );

			if ( '' === $title || '' === $item_url ) {
				continue;
			}

			$rows[] = array(
				'title'   => $title,
				'url'     => $item_url,
				'content' => wp_kses_post( (string) $item->get_description() ),
			);
		}

		return $rows;
	}

	private function error( string $code, string $message, int $status ): WP_Error {
		return new WP_Error( $code, $message, array( 'status' => $status ) );
	}
}

<?php
/**
 * Imports curated RSS items as reviewable WordPress drafts.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\Editorial;

use WP_Error;

defined( 'ABSPATH' ) || exit;

final class RssImportService {
	private const MAX_ITEMS = 50;

	public function __construct(
		private RssFeedReader $reader,
		private EditorialLanguageGateway $languages,
		private EditorialMeta $meta
	) {
	}

	/**
	 * @return array{
	 *   rows: list<array{status: string, post_id: int|string, title: string, url: string, message: string}>,
	 *   counts: array{imported: int, would_import: int, duplicate: int, failed: int}
	 * }|WP_Error
	 */
	public function import(
		string $feed_url,
		string $source_name,
		string $language,
		int $limit,
		bool $dry_run
	): array|WP_Error {
		$source_name = $this->meta->sanitize_text( $source_name );
		$language    = sanitize_key( $language );

		if ( '' === $source_name || strlen( $source_name ) > 120 ) {
			return $this->error( 'alborsa_platform_rss_invalid_source', __( 'A source name of 120 characters or fewer is required.', 'alborsa-platform' ) );
		}

		if ( ! $this->languages->available() ) {
			return $this->error( 'alborsa_platform_rss_language_unavailable', __( 'Polylang must be available for an explicit-language RSS import.', 'alborsa-platform' ) );
		}

		if ( '' === $language || ! $this->languages->has_language( $language ) ) {
			return $this->error( 'alborsa_platform_rss_invalid_language', __( 'The requested RSS import language is not configured in Polylang.', 'alborsa-platform' ) );
		}

		if ( $limit < 1 || $limit > self::MAX_ITEMS ) {
			return $this->error( 'alborsa_platform_rss_invalid_limit', __( 'The RSS import limit must be between 1 and 50.', 'alborsa-platform' ) );
		}

		$items = $this->reader->read( $feed_url, $limit );

		if ( $items instanceof WP_Error ) {
			return $items;
		}

		$report = $this->empty_report();
		$seen   = array();

		foreach ( $items as $item ) {
			$this->import_item( $item, $source_name, $language, $dry_run, $seen, $report );
		}

		return $report;
	}

	/**
	 * @param array{title: string, url: string, content: string} $item Feed item.
	 * @param array<string, true> $seen Feed hashes already processed in this run.
	 * @param array{
	 *   rows: list<array{status: string, post_id: int|string, title: string, url: string, message: string}>,
	 *   counts: array{imported: int, would_import: int, duplicate: int, failed: int}
	 * } $report Import report updated in place.
	 */
	private function import_item(
		array $item,
		string $source_name,
		string $language,
		bool $dry_run,
		array &$seen,
		array &$report
	): void {
		$hash = hash( 'sha256', $item['url'] );

		if ( isset( $seen[ $hash ] ) || $this->already_imported( $hash ) ) {
			$seen[ $hash ] = true;
			$this->add_row( $report, 'duplicate', '', $item, __( 'Already imported.', 'alborsa-platform' ) );
			return;
		}

		$seen[ $hash ] = true;

		if ( $dry_run ) {
			$this->add_row( $report, 'would_import', '', $item, '' );
			return;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => 'post',
				'post_status'  => 'draft',
				'post_title'   => $item['title'],
				'post_content' => wp_kses_post( $item['content'] ),
				'post_excerpt' => wp_trim_words( wp_strip_all_tags( $item['content'] ), 40, '…' ),
				'post_author'  => get_current_user_id(),
			),
			true
		);

		if ( $post_id instanceof WP_Error ) {
			$this->add_row( $report, 'failed', '', $item, __( 'WordPress could not create the draft.', 'alborsa-platform' ) );
			return;
		}

		update_post_meta( $post_id, EditorialMeta::SOURCE_NAME, $source_name );
		update_post_meta( $post_id, EditorialMeta::SOURCE_URL, $item['url'] );
		update_post_meta( $post_id, EditorialMeta::RSS_ITEM_HASH, $hash );
		$this->languages->set_post_language( $post_id, $language );
		$this->add_row( $report, 'imported', $post_id, $item, '' );
	}

	private function already_imported( string $hash ): bool {
		$matches = get_posts(
			array(
				'post_type'              => 'post',
				'post_status'            => 'any',
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'suppress_filters'       => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_key'               => EditorialMeta::RSS_ITEM_HASH,
				'meta_value'             => $hash,
			)
		);

		return array() !== $matches;
	}

	/**
	 * @return array{
	 *   rows: list<array{status: string, post_id: int|string, title: string, url: string, message: string}>,
	 *   counts: array{imported: int, would_import: int, duplicate: int, failed: int}
	 * }
	 */
	private function empty_report(): array {
		return array(
			'rows'   => array(),
			'counts' => array(
				'imported'     => 0,
				'would_import' => 0,
				'duplicate'    => 0,
				'failed'       => 0,
			),
		);
	}

	/**
	 * @param array{
	 *   rows: list<array{status: string, post_id: int|string, title: string, url: string, message: string}>,
	 *   counts: array{imported: int, would_import: int, duplicate: int, failed: int}
	 * } $report Import report updated in place.
	 * @param array{title: string, url: string, content: string} $item Feed item.
	 */
	private function add_row( array &$report, string $status, int|string $post_id, array $item, string $message ): void {
		$report['rows'][] = array(
			'status'  => $status,
			'post_id' => $post_id,
			'title'   => $item['title'],
			'url'     => $item['url'],
			'message' => $message,
		);

		if ( 'imported' === $status ) {
			++$report['counts']['imported'];
		} elseif ( 'would_import' === $status ) {
			++$report['counts']['would_import'];
		} elseif ( 'duplicate' === $status ) {
			++$report['counts']['duplicate'];
		} else {
			++$report['counts']['failed'];
		}
	}

	private function error( string $code, string $message ): WP_Error {
		return new WP_Error( $code, $message, array( 'status' => 400 ) );
	}
}

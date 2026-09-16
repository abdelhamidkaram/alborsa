<?php
/**
 * WP-CLI entry point for curated RSS draft imports.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\Editorial;

defined( 'ABSPATH' ) || exit;

final class RssImportCommand {
	public function __construct( private RssImportService $importer ) {
	}

	/**
	 * Imports RSS items as drafts for human review.
	 *
	 * ## OPTIONS
	 *
	 * <url>
	 * : Safe public RSS or Atom URL.
	 *
	 * --source=<name>
	 * : Human-readable source name.
	 *
	 * --lang=<slug>
	 * : Explicit configured Polylang language slug.
	 *
	 * [--limit=<number>]
	 * : Maximum items, from 1 to 50. Default: 20.
	 *
	 * [--dry-run]
	 * : Report without writing drafts.
	 *
	 * ## EXAMPLES
	 *
	 *     wp alborsa editorial import-rss https://example.com/feed --source="Example" --lang=ar --dry-run
	 *
	 * @param list<string> $args Positional arguments.
	 * @param array<string, mixed> $assoc_args Named arguments.
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		$url      = isset( $args[0] ) ? (string) $args[0] : '';
		$source   = isset( $assoc_args['source'] ) ? (string) $assoc_args['source'] : '';
		$language = isset( $assoc_args['lang'] ) ? (string) $assoc_args['lang'] : '';
		$limit    = isset( $assoc_args['limit'] ) && is_numeric( $assoc_args['limit'] ) ? (int) $assoc_args['limit'] : 20;
		$report   = $this->importer->import( $url, $source, $language, $limit, isset( $assoc_args['dry-run'] ) );

		if ( is_wp_error( $report ) ) {
			\WP_CLI::error( $report->get_error_message() );
			return;
		}

		\WP_CLI\Utils\format_items( 'table', $report['rows'], array( 'status', 'post_id', 'title', 'url', 'message' ) );
		$summary = sprintf(
			/* translators: 1: imported, 2: dry-run imports, 3: duplicates, 4: failures. */
			__( 'imported=%1$d would_import=%2$d duplicate=%3$d failed=%4$d', 'alborsa-platform' ),
			$report['counts']['imported'],
			$report['counts']['would_import'],
			$report['counts']['duplicate'],
			$report['counts']['failed']
		);

		if ( $report['counts']['failed'] > 0 ) {
			\WP_CLI::error( $summary );
			return;
		}

		\WP_CLI::success( $summary );
	}
}

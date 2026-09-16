<?php
/**
 * WP-CLI command for the asset CSV importer.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\Assets;

defined( 'ABSPATH' ) || exit;

final class AssetImportCommand {
	private AssetCsvImporter $importer;

	public function __construct( AssetCsvImporter $importer ) {
		$this->importer = $importer;
	}

	/**
	 * Imports asset rows from a UTF-8 CSV file.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : Path to the CSV file.
	 *
	 * [--dry-run]
	 * : Validate and report actions without changing WordPress.
	 *
	 * ## EXAMPLES
	 *
	 *     wp alborsa assets import assets.csv --dry-run
	 *
	 * @param list<string> $args Positional command arguments.
	 * @param array<string, mixed> $assoc_args Named command arguments.
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		$path    = isset( $args[0] ) ? (string) $args[0] : '';
		$dry_run = isset( $assoc_args['dry-run'] );
		$report  = $this->importer->import( $path, $dry_run );

		if ( is_wp_error( $report ) ) {
			\WP_CLI::error( $report->get_error_message() );
			return;
		}

		\WP_CLI\Utils\format_items(
			'table',
			$report['rows'],
			array( 'row', 'status', 'post_id', 'message' )
		);

		$summary = sprintf(
			/* translators: 1: created count, 2: updated count, 3: dry-run create count, 4: dry-run update count, 5: failure count. */
			__( 'created=%1$d updated=%2$d would_create=%3$d would_update=%4$d failed=%5$d', 'alborsa-platform' ),
			$report['counts']['created'],
			$report['counts']['updated'],
			$report['counts']['would_create'],
			$report['counts']['would_update'],
			$report['counts']['failed']
		);

		if ( $report['counts']['failed'] > 0 ) {
			\WP_CLI::error( $summary );
			return;
		}

		\WP_CLI::success( $summary );
	}
}

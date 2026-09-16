<?php
/**
 * Coordinates bounded CSV reads and validated asset upserts.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\Assets;

use WP_Error;

defined( 'ABSPATH' ) || exit;

final class AssetCsvImporter {
	private AssetCsvReader $reader;
	private AssetUpsertService $assets;

	public function __construct( AssetCsvReader $reader, AssetUpsertService $assets ) {
		$this->reader = $reader;
		$this->assets = $assets;
	}

	/**
	 * @return array{
	 *   rows: list<array{row: int, status: string, post_id: int|string, message: string}>,
	 *   counts: array<string, int>
	 * }|WP_Error
	 */
	public function import( string $path, bool $dry_run ): array|WP_Error {
		$csv = $this->reader->read( $path );

		if ( $csv instanceof WP_Error ) {
			return $csv;
		}

		$report = array(
			'rows'   => array(),
			'counts' => array(
				'created'      => 0,
				'updated'      => 0,
				'would_create' => 0,
				'would_update' => 0,
				'failed'       => 0,
			),
		);
		$seen   = array();

		foreach ( $csv['rows'] as $row ) {
			$this->import_row( $row, $dry_run, $seen, $report );
		}

		return $report;
	}

	/**
	 * @param array{line: int, data: array<string, string>, error: string} $row CSV row.
	 * @param array<string, int> $seen First row number for each identity.
	 * @param array{
	 *   rows: list<array{row: int, status: string, post_id: int|string, message: string}>,
	 *   counts: array<string, int>
	 * } $report Import report updated in place.
	 */
	private function import_row( array $row, bool $dry_run, array &$seen, array &$report ): void {
		if ( '' !== $row['error'] ) {
			$this->add_error( $report, $row['line'], $row['error'] );
			return;
		}

		$identity_key = $this->assets->identity_key( $row['data'] );

		if ( $identity_key instanceof WP_Error ) {
			$this->add_error( $report, $row['line'], $identity_key->get_error_message() );
			return;
		}

		if ( isset( $seen[ $identity_key ] ) ) {
			$this->add_error(
				$report,
				$row['line'],
				sprintf(
					/* translators: %d: first CSV row number with this asset identity. */
					__( 'Duplicate import identity; first seen on row %d.', 'alborsa-platform' ),
					$seen[ $identity_key ]
				)
			);
			return;
		}

		$result = $this->assets->upsert( $row['data'], $dry_run );

		if ( $result instanceof WP_Error ) {
			$this->add_error( $report, $row['line'], $result->get_error_message() );
			return;
		}

		$seen[ $identity_key ] = $row['line'];
		$report['rows'][]      = array(
			'row'     => $row['line'],
			'status'  => $result['status'],
			'post_id' => 0 === $result['post_id'] ? '' : $result['post_id'],
			'message' => '',
		);
		++$report['counts'][ $result['status'] ];
	}

	/**
	 * @param array{
	 *   rows: list<array{row: int, status: string, post_id: int|string, message: string}>,
	 *   counts: array<string, int>
	 * } $report Import report updated in place.
	 */
	private function add_error( array &$report, int $line, string $message ): void {
		$report['rows'][] = array(
			'row'     => $line,
			'status'  => 'failed',
			'post_id' => '',
			'message' => $message,
		);
		++$report['counts']['failed'];
	}
}

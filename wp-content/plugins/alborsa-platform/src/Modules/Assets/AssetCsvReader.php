<?php
/**
 * Bounded CSV reader for asset imports.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\Assets;

use WP_Error;

defined( 'ABSPATH' ) || exit;

final class AssetCsvReader {
	private const MAX_FILE_BYTES = 5242880;
	private const MAX_ROWS       = 5000;

	private const REQUIRED_HEADERS = array(
		'language',
		'title',
		'canonical_symbol',
		'asset_type',
	);

	private const ALLOWED_HEADERS = array(
		'language',
		'title',
		'slug',
		'post_status',
		'excerpt',
		'content',
		'canonical_symbol',
		'asset_type',
		'exchange',
		'sector',
		'base_currency',
		'quote_currency',
		'isin',
		'official_website',
		'listing_status',
	);

	/**
	 * @return array{rows: list<array{line: int, data: array<string, string>, error: string}>}|WP_Error
	 */
	public function read( string $path ): array|WP_Error {
		$path_error = $this->validate_path( $path );

		if ( $path_error instanceof WP_Error ) {
			return $path_error;
		}

		try {
			$file = new \SplFileObject( $path, 'r' );
		} catch ( \RuntimeException ) {
			return $this->file_error( 'alborsa_platform_asset_csv_unreadable', __( 'The CSV file could not be opened.', 'alborsa-platform' ) );
		}

		$file->setCsvControl( ',', '"', '' );
		$file->setFlags( \SplFileObject::READ_CSV | \SplFileObject::DROP_NEW_LINE | \SplFileObject::SKIP_EMPTY );

		$headers = $this->headers( $file->fgetcsv() );

		if ( $headers instanceof WP_Error ) {
			return $headers;
		}

		return $this->data_rows( $file, $headers );
	}

	private function validate_path( string $path ): ?WP_Error {
		if ( '' === $path || ! is_file( $path ) || ! is_readable( $path ) ) {
			return $this->file_error( 'alborsa_platform_asset_csv_unreadable', __( 'The CSV file does not exist or is not readable.', 'alborsa-platform' ) );
		}

		$file_size = filesize( $path );

		if ( false === $file_size || $file_size > self::MAX_FILE_BYTES ) {
			return $this->file_error( 'alborsa_platform_asset_csv_too_large', __( 'The CSV file must not exceed 5 MB.', 'alborsa-platform' ) );
		}

		return null;
	}

	/**
	 * @param list<string|null>|false $raw_headers Raw first CSV row.
	 * @return list<string>|WP_Error
	 */
	private function headers( array|false $raw_headers ): array|WP_Error {
		if ( false === $raw_headers || $this->is_empty_row( $raw_headers ) ) {
			return $this->file_error( 'alborsa_platform_asset_csv_empty', __( 'The CSV file has no header row.', 'alborsa-platform' ) );
		}

		if ( ! $this->is_valid_utf8( $raw_headers ) ) {
			return $this->file_error( 'alborsa_platform_asset_csv_invalid_encoding', __( 'The CSV file must use valid UTF-8 encoding.', 'alborsa-platform' ) );
		}

		$headers = array_map(
			static fn( ?string $header ): string => strtolower( trim( ltrim( (string) $header, "\xEF\xBB\xBF" ) ) ),
			$raw_headers
		);

		if ( count( $headers ) !== count( array_unique( $headers ) ) ) {
			return $this->file_error( 'alborsa_platform_asset_csv_duplicate_header', __( 'The CSV header contains duplicate columns.', 'alborsa-platform' ) );
		}

		$unknown_headers = array_diff( $headers, self::ALLOWED_HEADERS );

		if ( array() !== $unknown_headers ) {
			return $this->file_error(
				'alborsa_platform_asset_csv_unknown_header',
				sprintf(
					/* translators: %s: comma-separated CSV column names. */
					__( 'Unknown CSV columns: %s.', 'alborsa-platform' ),
					implode( ', ', $unknown_headers )
				)
			);
		}

		$missing_headers = array_diff( self::REQUIRED_HEADERS, $headers );

		if ( array() !== $missing_headers ) {
			return $this->file_error(
				'alborsa_platform_asset_csv_missing_header',
				sprintf(
					/* translators: %s: comma-separated CSV column names. */
					__( 'Missing required CSV columns: %s.', 'alborsa-platform' ),
					implode( ', ', $missing_headers )
				)
			);
		}

		return $headers;
	}

	/**
	 * @param list<string> $headers Validated headers.
	 * @return array{rows: list<array{line: int, data: array<string, string>, error: string}>}|WP_Error
	 */
	private function data_rows( \SplFileObject $file, array $headers ): array|WP_Error {
		$rows = array();

		while ( ! $file->eof() ) {
			$raw_row = $file->fgetcsv();
			$line    = $file->key() + 1;

			if ( false === $raw_row ) {
				continue;
			}

			$csv_row = $this->normalize_csv_row( $raw_row );

			if ( $this->is_empty_row( $csv_row ) ) {
				continue;
			}

			if ( count( $rows ) >= self::MAX_ROWS ) {
				return $this->file_error( 'alborsa_platform_asset_csv_too_many_rows', __( 'The CSV file must not exceed 5000 data rows.', 'alborsa-platform' ) );
			}

			$rows[] = $this->data_row( $headers, $csv_row, $line );
		}

		return array( 'rows' => $rows );
	}

	/**
	 * @param list<string>      $headers Validated headers.
	 * @param list<string|null> $raw_row Raw CSV row.
	 * @return array{line: int, data: array<string, string>, error: string}
	 */
	private function data_row( array $headers, array $raw_row, int $line ): array {
		if ( ! $this->is_valid_utf8( $raw_row ) ) {
			return array(
				'line'  => $line,
				'data'  => array(),
				'error' => __( 'The row contains invalid UTF-8 text.', 'alborsa-platform' ),
			);
		}

		if ( count( $headers ) !== count( $raw_row ) ) {
			return array(
				'line'  => $line,
				'data'  => array(),
				'error' => __( 'Column count does not match the header row.', 'alborsa-platform' ),
			);
		}

		$values = array_map( static fn( ?string $value ): string => trim( (string) $value ), $raw_row );
		$data   = array_combine( $headers, $values );

		return array(
			'line'  => $line,
			'data'  => $data,
			'error' => '',
		);
	}

	/**
	 * @param array<mixed> $row Raw row from SplFileObject.
	 * @return list<string|null>
	 */
	private function normalize_csv_row( array $row ): array {
		$normalized = array();

		foreach ( $row as $value ) {
			$normalized[] = is_string( $value ) ? $value : null;
		}

		return $normalized;
	}

	/**
	 * @param list<string|null> $row CSV row.
	 */
	private function is_empty_row( array $row ): bool {
		return array() === array_filter(
			$row,
			static fn( ?string $value ): bool => '' !== trim( (string) $value )
		);
	}

	/**
	 * @param list<string|null> $values CSV values.
	 */
	private function is_valid_utf8( array $values ): bool {
		foreach ( $values as $value ) {
			if ( null !== $value && 1 !== preg_match( '//u', $value ) ) {
				return false;
			}
		}

		return true;
	}

	private function file_error( string $code, string $message ): WP_Error {
		return new WP_Error( $code, $message );
	}
}

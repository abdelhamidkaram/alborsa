<?php
/**
 * Asset CSV reader tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\Assets;

use Alborsa\Platform\Modules\Assets\AssetCsvReader;
use Alborsa\Platform\Tests\UnitTestCase;

final class AssetCsvReaderTest extends UnitTestCase {
	/** @var list<string> */
	private array $temporary_files = array();

	protected function tearDown(): void {
		foreach ( $this->temporary_files as $path ) {
			if ( is_file( $path ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Removes only test-owned temporary files.
				unlink( $path );
			}
		}

		parent::tearDown();
	}

	public function test_reader_accepts_utf8_bom_and_maps_valid_rows(): void {
		$path = $this->csv( "\xEF\xBB\xBFlanguage,title,canonical_symbol,asset_type\nar,Saudi Aramco,2222,stock\n" );

		$result = ( new AssetCsvReader() )->read( $path );

		self::assertFalse( is_wp_error( $result ) );
		self::assertCount( 1, $result['rows'] );
		self::assertSame( 2, $result['rows'][0]['line'] );
		self::assertSame( '2222', $result['rows'][0]['data']['canonical_symbol'] );
	}

	public function test_reader_rejects_unknown_header(): void {
		$path = $this->csv( "language,title,canonical_symbol,asset_type,typo\nar,Aramco,2222,stock,value\n" );

		$result = ( new AssetCsvReader() )->read( $path );

		self::assertTrue( is_wp_error( $result ) );
		self::assertSame( 'alborsa_platform_asset_csv_unknown_header', $result->get_error_code() );
	}

	public function test_reader_reports_malformed_row_without_discarding_file(): void {
		$path = $this->csv( "language,title,canonical_symbol,asset_type\nar,Aramco,2222\n" );

		$result = ( new AssetCsvReader() )->read( $path );

		self::assertFalse( is_wp_error( $result ) );
		self::assertSame( 'Column count does not match the header row.', $result['rows'][0]['error'] );
	}

	public function test_reader_reports_invalid_utf8_row(): void {
		$path = $this->csv( "language,title,canonical_symbol,asset_type\nar,Invalid\xFF,2222,stock\n" );

		$result = ( new AssetCsvReader() )->read( $path );

		self::assertFalse( is_wp_error( $result ) );
		self::assertSame( 'The row contains invalid UTF-8 text.', $result['rows'][0]['error'] );
	}

	private function csv( string $contents ): string {
		$path = tempnam( sys_get_temp_dir(), 'alborsa-csv-' );

		if ( false === $path ) {
			self::fail( 'Unable to create temporary CSV file.' );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Builds an isolated test fixture.
		file_put_contents( $path, $contents );
		$this->temporary_files[] = $path;

		return $path;
	}
}

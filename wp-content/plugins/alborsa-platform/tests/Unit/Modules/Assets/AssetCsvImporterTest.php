<?php
/**
 * Asset CSV importer tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\Assets;

use Alborsa\Platform\Modules\Assets\AssetCsvImporter;
use Alborsa\Platform\Modules\Assets\AssetCsvReader;
use Alborsa\Platform\Modules\Assets\AssetMeta;
use Alborsa\Platform\Modules\Assets\AssetUpsertService;
use Alborsa\Platform\Modules\Assets\AssetsModule;
use Alborsa\Platform\Tests\Support\AssetLanguageGatewayDouble;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;

final class AssetCsvImporterTest extends UnitTestCase {
	private string $temporary_file = '';

	protected function tearDown(): void {
		if ( '' !== $this->temporary_file && is_file( $this->temporary_file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Removes only a test-owned temporary file.
			unlink( $this->temporary_file );
		}

		parent::tearDown();
	}

	public function test_duplicate_identity_in_same_file_is_reported(): void {
		$this->stub_boundaries();
		$this->temporary_file = $this->csv(
			"language,title,canonical_symbol,asset_type\nar,Aramco,2222,stock\nar,Aramco duplicate,2222,stock\n"
		);

		$assets   = new AssetUpsertService( new AssetMeta(), new AssetLanguageGatewayDouble() );
		$importer = new AssetCsvImporter( new AssetCsvReader(), $assets );
		$report   = $importer->import( $this->temporary_file, true );

		self::assertFalse( is_wp_error( $report ) );
		self::assertSame( 1, $report['counts']['would_create'] );
		self::assertSame( 1, $report['counts']['failed'] );
		self::assertSame( 'failed', $report['rows'][1]['status'] );
	}

	public function test_failed_row_does_not_block_later_valid_identity(): void {
		$this->stub_boundaries();
		$this->temporary_file = $this->csv(
			"language,title,canonical_symbol,asset_type\nar,,2222,stock\nar,Saudi Aramco,2222,stock\n"
		);

		$assets   = new AssetUpsertService( new AssetMeta(), new AssetLanguageGatewayDouble() );
		$importer = new AssetCsvImporter( new AssetCsvReader(), $assets );
		$report   = $importer->import( $this->temporary_file, true );

		self::assertFalse( is_wp_error( $report ) );
		self::assertSame( 1, $report['counts']['failed'] );
		self::assertSame( 1, $report['counts']['would_create'] );
		self::assertSame( 'would_create', $report['rows'][1]['status'] );
	}

	private function stub_boundaries(): void {
		Functions\when( 'sanitize_text_field' )->alias( static fn( string $value ): string => trim( $value ) );
		Functions\when( 'sanitize_key' )->alias( static fn( string $value ): string => strtolower( $value ) );
		Functions\when( 'sanitize_title' )->alias( static fn( string $value ): string => strtolower( $value ) );
		Functions\when( 'esc_url_raw' )->returnArg();
		Functions\when( 'wp_kses_post' )->returnArg();
		Functions\when( 'term_exists' )->alias(
			static fn( string $slug, string $taxonomy ): int => AssetsModule::TYPE_TAXONOMY === $taxonomy ? 11 : 12
		);
		Functions\when( 'get_posts' )->justReturn( array() );
	}

	private function csv( string $contents ): string {
		$path = tempnam( sys_get_temp_dir(), 'alborsa-import-' );

		if ( false === $path ) {
			self::fail( 'Unable to create temporary CSV file.' );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Builds an isolated test fixture.
		file_put_contents( $path, $contents );

		return $path;
	}
}

<?php
/**
 * Asset upsert service tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\Assets;

use Alborsa\Platform\Modules\Assets\AssetMeta;
use Alborsa\Platform\Modules\Assets\AssetUpsertService;
use Alborsa\Platform\Modules\Assets\AssetsModule;
use Alborsa\Platform\Tests\Support\AssetLanguageGatewayDouble;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;

final class AssetUpsertServiceTest extends UnitTestCase {
	public function test_dry_run_reports_create_without_writing(): void {
		$this->stub_boundaries();
		Functions\when( 'get_posts' )->justReturn( array() );
		Functions\expect( 'wp_insert_post' )->never();

		$result = $this->service()->upsert( $this->valid_row(), true );

		self::assertSame(
			array(
				'status'  => 'would_create',
				'post_id' => 0,
			),
			$result
		);
	}

	public function test_dry_run_reports_update_for_existing_language_symbol(): void {
		$this->stub_boundaries();
		Functions\when( 'get_posts' )->justReturn( array( 55 ) );

		$result = $this->service()->upsert( $this->valid_row(), true );

		self::assertSame(
			array(
				'status'  => 'would_update',
				'post_id' => 55,
			),
			$result
		);
	}

	public function test_create_writes_language_metadata_and_existing_terms(): void {
		$this->stub_boundaries();
		$languages = new AssetLanguageGatewayDouble();
		Functions\when( 'get_posts' )->justReturn( array() );
		Functions\expect( 'wp_insert_post' )
			->once()
			->with(
				\Mockery::on(
					static fn( array $post ): bool => AssetsModule::POST_TYPE === $post['post_type']
						&& 'draft' === $post['post_status']
						&& '2222' === $post['post_name']
				),
				true
			)
			->andReturn( 101 );
		Functions\expect( 'update_post_meta' )->once()->andReturn( true );
		Functions\expect( 'wp_set_object_terms' )->once()->andReturn( array( 11 ) );

		$result = $this->service( $languages )->upsert( $this->valid_row(), false );

		self::assertSame(
			array(
				'status'  => 'created',
				'post_id' => 101,
			),
			$result
		);
		self::assertSame(
			array(
				array(
					'post_id'  => 101,
					'language' => 'ar',
				),
			),
			$languages->assigned_posts
		);
	}

	public function test_update_preserves_optional_values_missing_from_csv(): void {
		$this->stub_boundaries();
		Functions\when( 'get_posts' )->justReturn( array( 55 ) );
		Functions\expect( 'wp_insert_post' )
			->once()
			->with(
				\Mockery::on(
					static fn( array $post ): bool => 55 === $post['ID']
						&& ! isset( $post['post_status'] )
						&& ! isset( $post['post_excerpt'] )
						&& ! isset( $post['post_content'] )
						&& ! isset( $post['post_name'] )
				),
				true
			)
			->andReturn( 55 );
		Functions\expect( 'update_post_meta' )->once()->andReturn( true );
		Functions\expect( 'wp_set_object_terms' )->once()->andReturn( array( 11 ) );

		$result = $this->service()->upsert( $this->valid_row(), false );

		self::assertSame(
			array(
				'status'  => 'updated',
				'post_id' => 55,
			),
			$result
		);
	}

	public function test_english_asset_cannot_be_published_in_current_phase(): void {
		$this->stub_sanitizers();

		$row                = $this->valid_row();
		$row['language']    = 'en';
		$row['post_status'] = 'publish';
		$result             = $this->service()->upsert( $row, true );

		self::assertTrue( is_wp_error( $result ) );
		self::assertSame( 'alborsa_platform_asset_import_english_not_publishable', $result->get_error_code() );
	}

	public function test_unknown_asset_type_is_rejected_before_writing(): void {
		$this->stub_sanitizers();
		Functions\when( 'term_exists' )->justReturn( null );

		$result = $this->service()->upsert( $this->valid_row(), false );

		self::assertTrue( is_wp_error( $result ) );
		self::assertSame( 'alborsa_platform_asset_import_unknown_term', $result->get_error_code() );
	}

	private function service( ?AssetLanguageGatewayDouble $languages = null ): AssetUpsertService {
		return new AssetUpsertService( new AssetMeta(), $languages ?? new AssetLanguageGatewayDouble() );
	}

	private function stub_boundaries(): void {
		$this->stub_sanitizers();
		Functions\when( 'term_exists' )->alias(
			static fn( string $slug, string $taxonomy ): int => AssetsModule::TYPE_TAXONOMY === $taxonomy ? 11 : 12
		);
	}

	private function stub_sanitizers(): void {
		Functions\when( 'sanitize_text_field' )->alias( static fn( string $value ): string => trim( $value ) );
		Functions\when( 'sanitize_key' )->alias(
			static fn( string $value ): string => preg_replace( '/[^a-z0-9_-]/', '', strtolower( $value ) ) ?? ''
		);
		Functions\when( 'sanitize_title' )->alias(
			static fn( string $value ): string => strtolower( preg_replace( '/[^A-Za-z0-9]+/', '-', trim( $value ) ) ?? '' )
		);
		Functions\when( 'esc_url_raw' )->alias( static fn( string $value ): string => $value );
		Functions\when( 'wp_kses_post' )->returnArg();
		Functions\when( 'wp_slash' )->returnArg();
	}

	/** @return array<string, string> */
	private function valid_row(): array {
		return array(
			'language'         => 'ar',
			'title'            => 'Saudi Aramco',
			'canonical_symbol' => '2222',
			'asset_type'       => 'stock',
		);
	}
}

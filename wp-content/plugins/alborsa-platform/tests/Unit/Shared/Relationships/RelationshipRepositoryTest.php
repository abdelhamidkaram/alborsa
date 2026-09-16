<?php
/**
 * Relationship repository tests.
 *
 * These isolated tests verify repository orchestration only. They do not prove
 * MySQL schema behavior, which remains covered by the WordPress integration boundary.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Shared\Relationships;

use Alborsa\Platform\Shared\Relationships\RelationshipRepository;
use Alborsa\Platform\Tests\Support\WpdbDouble;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;

final class RelationshipRepositoryTest extends UnitTestCase {
	public function test_add_inserts_valid_relationship_with_utc_timestamp_and_explicit_formats(): void {
		$wpdb            = new WpdbDouble();
		$wpdb->insert_id = 42;
		$this->set_wpdb_for_test( $wpdb );
		Functions\expect( 'current_time' )
			->once()
			->with( 'mysql', true )
			->andReturn( '2026-09-16 10:20:30' );

		$repository = new RelationshipRepository( $wpdb );
		$result     = $repository->add( $this->relationship() );

		self::assertSame( 42, $result );
		self::assertSame( 'wp_alb_relationships', substr( (string) $wpdb->last_query, 12 ) );
		self::assertSame( 10, $wpdb->last_insert_data['from_object_id'] );
		self::assertSame( '2026-09-16 10:20:30', $wpdb->last_insert_data['created_at'] );
		self::assertSame( array( '%d', '%s', '%d', '%s', '%s', '%d', '%s' ), $wpdb->last_insert_formats );
	}

	public function test_add_rejects_invalid_ids_and_keys_without_truncating_them(): void {
		$wpdb = new WpdbDouble();
		$this->set_wpdb_for_test( $wpdb );
		$repository = new RelationshipRepository( $wpdb );

		$invalid_id  = $repository->add( array_merge( $this->relationship(), array( 'from_object_id' => 0 ) ) );
		$invalid_key = $repository->add( array_merge( $this->relationship(), array( 'relation_type' => str_repeat( 'a', 33 ) ) ) );

		self::assertTrue( is_wp_error( $invalid_id ) );
		self::assertSame( 'alborsa_platform_relationship_invalid_id', $invalid_id->get_error_code() );
		self::assertTrue( is_wp_error( $invalid_key ) );
		self::assertSame( 'alborsa_platform_relationship_invalid_key', $invalid_key->get_error_code() );
		self::assertNull( $wpdb->last_insert_data );
	}

	public function test_add_returns_actionable_error_when_insert_fails(): void {
		$wpdb = new class() extends WpdbDouble {
			public function insert( string $table, array $data, array $format = array() ): int|false {
				parent::insert( $table, $data, $format );
				$this->last_error = 'Duplicate relationship';

				return false;
			}
		};
		$this->set_wpdb_for_test( $wpdb );
		Functions\when( 'current_time' )->justReturn( '2026-09-16 10:20:30' );

		$result = ( new RelationshipRepository( $wpdb ) )->add( $this->relationship() );

		self::assertTrue( is_wp_error( $result ) );
		self::assertSame( 'alborsa_platform_relationship_create_failed', $result->get_error_code() );
		self::assertStringContainsString( 'Duplicate relationship', $result->get_error_message() );
	}

	public function test_delete_prepares_an_exact_identity_and_returns_whether_it_deleted_a_row(): void {
		$wpdb = new WpdbDouble();
		$this->set_wpdb_for_test( $wpdb );
		$repository = new RelationshipRepository( $wpdb );

		self::assertTrue( $repository->delete( $this->relationship() ) );
		self::assertStringContainsString( 'wp_alb_relationships', (string) $wpdb->last_query );
		self::assertStringContainsString( "from_object_type = 'post'", (string) $wpdb->last_query );

		$wpdb_no_match = new class() extends WpdbDouble {
			public function query( string $query ): int|false {
				parent::query( $query );

				return 0;
			}
		};
		self::assertFalse( ( new RelationshipRepository( $wpdb_no_match ) )->delete( $this->relationship() ) );
	}

	public function test_find_by_source_and_target_return_list_shaped_rows_in_database_order(): void {
		$wpdb = new class() extends WpdbDouble {
			public function get_results( string $query, string|int $output = 'OBJECT' ): ?array {
				parent::get_results( $query, $output );

				return array(
					7 => array(
						'id'         => '8',
						'sort_order' => '0',
					),
					9 => array(
						'id'         => '10',
						'sort_order' => '1',
					),
				);
			}
		};
		$this->set_wpdb_for_test( $wpdb );
		$repository = new RelationshipRepository( $wpdb );

		$source_rows = $repository->find_by_source( 10, 'post' );
		self::assertSame(
			array(
				array(
					'id'         => '8',
					'sort_order' => '0',
				),
				array(
					'id'         => '10',
					'sort_order' => '1',
				),
			),
			$source_rows
		);
		self::assertStringContainsString( "from_object_type = 'post'", (string) $wpdb->last_query );
		self::assertStringContainsString( 'ORDER BY sort_order ASC, id ASC', (string) $wpdb->last_query );
		self::assertStringContainsString( 'LIMIT 100', (string) $wpdb->last_query );

		$target_rows = $repository->find_by_target( 20, 'alb_asset' );
		self::assertCount( 2, $target_rows );
		self::assertStringContainsString( "to_object_type = 'alb_asset'", (string) $wpdb->last_query );
	}

	public function test_read_failure_returns_actionable_error(): void {
		$wpdb = new class() extends WpdbDouble {
			public function get_results( string $query, string|int $output = 'OBJECT' ): ?array {
				parent::get_results( $query, $output );
				$this->last_error = 'Table unavailable';

				return null;
			}
		};
		$this->set_wpdb_for_test( $wpdb );

		$result = ( new RelationshipRepository( $wpdb ) )->find_by_target( 20, 'alb_asset' );

		self::assertTrue( is_wp_error( $result ) );
		self::assertSame( 'alborsa_platform_relationship_read_failed', $result->get_error_code() );
	}

	/**
	 * @return array<string, int|string>
	 */
	private function relationship(): array {
		return array(
			'from_object_id'   => 10,
			'from_object_type' => 'post',
			'to_object_id'     => 20,
			'to_object_type'   => 'alb_asset',
			'relation_type'    => 'mentions',
			'sort_order'       => 3,
		);
	}
}

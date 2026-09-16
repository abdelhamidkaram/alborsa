<?php
/**
 * Persists internal content relationships.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Shared\Relationships;

use Alborsa\Platform\Foundation\MigrationRunner;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Minimal repository for the existing relationships table.
 */
final class RelationshipRepository {
	private \wpdb $wpdb;

	public function __construct( \wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Creates one relationship.
	 *
	 * @param array<string, mixed> $relationship Relationship fields.
	 * @return int|WP_Error Inserted relationship ID or an actionable error.
	 */
	public function add( array $relationship ): int|WP_Error {
		$validated = $this->validate_relationship( $relationship );

		if ( $validated instanceof WP_Error ) {
			return $validated;
		}

		$inserted = $this->wpdb->insert(
			$this->table_name(),
			array(
				'from_object_id'   => $validated['from_object_id'],
				'from_object_type' => $validated['from_object_type'],
				'to_object_id'     => $validated['to_object_id'],
				'to_object_type'   => $validated['to_object_type'],
				'relation_type'    => $validated['relation_type'],
				'sort_order'       => $validated['sort_order'],
				'created_at'       => current_time( 'mysql', true ),
			),
			array( '%d', '%s', '%d', '%s', '%s', '%d', '%s' )
		);

		if ( false === $inserted || $this->wpdb->insert_id <= 0 ) {
			return $this->database_error( 'create' );
		}

		return $this->wpdb->insert_id;
	}

	/**
	 * Deletes one exact relationship identity.
	 *
	 * @param array<string, mixed> $relationship Relationship identity fields.
	 * @return bool|WP_Error Whether a row was deleted or an actionable error.
	 */
	public function delete( array $relationship ): bool|WP_Error {
		$validated = $this->validate_relationship( $relationship );

		if ( $validated instanceof WP_Error ) {
			return $validated;
		}

		$query = $this->wpdb->prepare(
			'DELETE FROM %i WHERE from_object_id = %d AND from_object_type = %s AND to_object_id = %d AND to_object_type = %s AND relation_type = %s',
			$this->table_name(),
			$validated['from_object_id'],
			$validated['from_object_type'],
			$validated['to_object_id'],
			$validated['to_object_type'],
			$validated['relation_type']
		);
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The preceding wpdb::prepare() binds every value.
		$result = $this->wpdb->query( $query );

		if ( false === $result ) {
			return $this->database_error( 'delete' );
		}

		return $result > 0;
	}

	/**
	 * Finds relationships from one exact source object.
	 *
	 * @return list<array<string, mixed>>|WP_Error Relationship rows or an actionable error.
	 */
	public function find_by_source( int $object_id, string $object_type ): array|WP_Error {
		$validated = $this->validate_lookup( $object_id, $object_type );

		if ( $validated instanceof WP_Error ) {
			return $validated;
		}

		$query = $this->wpdb->prepare(
			'SELECT id, from_object_id, from_object_type, to_object_id, to_object_type, relation_type, sort_order, created_at FROM %i WHERE from_object_id = %d AND from_object_type = %s ORDER BY sort_order ASC, id ASC',
			$this->table_name(),
			$validated['object_id'],
			$validated['object_type']
		);

		return $this->read_results( $query );
	}

	/**
	 * Finds relationships to one exact target object.
	 *
	 * @return list<array<string, mixed>>|WP_Error Relationship rows or an actionable error.
	 */
	public function find_by_target( int $object_id, string $object_type ): array|WP_Error {
		$validated = $this->validate_lookup( $object_id, $object_type );

		if ( $validated instanceof WP_Error ) {
			return $validated;
		}

		$query = $this->wpdb->prepare(
			'SELECT id, from_object_id, from_object_type, to_object_id, to_object_type, relation_type, sort_order, created_at FROM %i WHERE to_object_id = %d AND to_object_type = %s ORDER BY sort_order ASC, id ASC',
			$this->table_name(),
			$validated['object_id'],
			$validated['object_type']
		);

		return $this->read_results( $query );
	}

	/**
	 * @return string
	 */
	private function table_name(): string {
		return $this->wpdb->prefix . MigrationRunner::RELATIONSHIPS_TABLE;
	}

	/**
	 * @param array<string, mixed> $relationship Relationship fields.
	 * @return array{from_object_id: int, from_object_type: string, to_object_id: int, to_object_type: string, relation_type: string, sort_order: int}|WP_Error
	 */
	private function validate_relationship( array $relationship ): array|WP_Error {
		$from_id = $this->positive_id( $relationship['from_object_id'] ?? null, 'source' );
		$to_id   = $this->positive_id( $relationship['to_object_id'] ?? null, 'target' );

		if ( $from_id instanceof WP_Error ) {
			return $from_id;
		}

		if ( $to_id instanceof WP_Error ) {
			return $to_id;
		}

		$from_type = $this->key( $relationship['from_object_type'] ?? null, 'source object type' );
		$to_type   = $this->key( $relationship['to_object_type'] ?? null, 'target object type' );
		$type      = $this->key( $relationship['relation_type'] ?? null, 'relationship type' );

		if ( $from_type instanceof WP_Error ) {
			return $from_type;
		}

		if ( $to_type instanceof WP_Error ) {
			return $to_type;
		}

		if ( $type instanceof WP_Error ) {
			return $type;
		}

		$sort_order = $this->sort_order( $relationship['sort_order'] ?? 0 );

		if ( $sort_order instanceof WP_Error ) {
			return $sort_order;
		}

		return array(
			'from_object_id'   => $from_id,
			'from_object_type' => $from_type,
			'to_object_id'     => $to_id,
			'to_object_type'   => $to_type,
			'relation_type'    => $type,
			'sort_order'       => $sort_order,
		);
	}

	/**
	 * @return array{object_id: int, object_type: string}|WP_Error
	 */
	private function validate_lookup( int $object_id, string $object_type ): array|WP_Error {
		$id   = $this->positive_id( $object_id, 'object' );
		$type = $this->key( $object_type, 'object type' );

		if ( $id instanceof WP_Error ) {
			return $id;
		}

		if ( $type instanceof WP_Error ) {
			return $type;
		}

		return array(
			'object_id'   => $id,
			'object_type' => $type,
		);
	}

	/**
	 * @return int|WP_Error
	 */
	private function positive_id( mixed $value, string $label ): int|WP_Error {
		if ( is_int( $value ) && $value > 0 ) {
			return $value;
		}

		if ( is_string( $value ) && preg_match( '/^[1-9][0-9]*$/D', $value ) ) {
			return (int) $value;
		}

		return new WP_Error(
			'alborsa_platform_relationship_invalid_id',
			sprintf(
				/* translators: %s: relationship side. */
				__( 'Alborsa Platform relationship %s ID must be a positive integer.', 'alborsa-platform' ),
				$label
			)
		);
	}

	/**
	 * @return string|WP_Error
	 */
	private function key( mixed $value, string $label ): string|WP_Error {
		if ( is_string( $value ) && preg_match( '/^[a-z0-9_-]{1,32}$/D', $value ) ) {
			return $value;
		}

		return new WP_Error(
			'alborsa_platform_relationship_invalid_key',
			sprintf(
				/* translators: %s: relationship field label. */
				__( 'Alborsa Platform relationship %s must use a nonempty WordPress-style key of at most 32 characters.', 'alborsa-platform' ),
				$label
			)
		);
	}

	/**
	 * @return int|WP_Error
	 */
	private function sort_order( mixed $value ): int|WP_Error {
		if ( is_int( $value ) ) {
			return $value;
		}

		if ( is_string( $value ) && preg_match( '/^-?(?:0|[1-9][0-9]*)$/D', $value ) ) {
			return (int) $value;
		}

		return new WP_Error(
			'alborsa_platform_relationship_invalid_sort_order',
			__( 'Alborsa Platform relationship sort order must be an integer.', 'alborsa-platform' )
		);
	}

	/**
	 * @return list<array<string, mixed>>|WP_Error
	 */
	private function read_results( string $query ): array|WP_Error {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The callers create the query with wpdb::prepare().
		$rows = $this->wpdb->get_results( $query, defined( 'ARRAY_A' ) ? ARRAY_A : 'ARRAY_A' );

		if ( null === $rows && '' !== $this->wpdb->last_error ) {
			return $this->database_error( 'read' );
		}

		if ( ! is_array( $rows ) ) {
			return array();
		}

		$results = array();

		foreach ( $rows as $row ) {
			if ( is_array( $row ) ) {
				$results[] = $row;
			}
		}

		return $results;
	}

	private function database_error( string $operation ): WP_Error {
		$detail = '' !== $this->wpdb->last_error ? ' ' . $this->wpdb->last_error : '';

		return new WP_Error(
			'alborsa_platform_relationship_' . $operation . '_failed',
			sprintf(
				/* translators: %s: database error detail. */
				__( 'Alborsa Platform could not %1$s the relationship.%2$s', 'alborsa-platform' ),
				$operation,
				$detail
			)
		);
	}
}

<?php
/**
 * Read-only broker and comparison REST endpoints.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\Brokers;

use WP_Error;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

final class BrokerRestController {
	public const NAMESPACE     = 'alborsa/v1';
	private const MAX_PER_PAGE = 100;
	private const MAX_PAGE     = 1000;

	public function __construct( private BrokerPresenter $presenter ) {
	}

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/brokers',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_collection' ),
				'permission_callback' => '__return_true',
				'args'                => $this->collection_arguments(),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/brokers/compare',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'compare' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'ids' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/brokers/(?P<id>[1-9][0-9]*)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_single' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'sanitize_callback' => 'absint',
						'validate_callback' => array( $this, 'is_positive_integer' ),
					),
				),
			)
		);
	}

	/** @return array<string, mixed>|WP_Error */
	public function get_collection( WP_REST_Request $request ): array|WP_Error {
		$page     = min( self::MAX_PAGE, max( 1, $this->integer_param( $request, 'page', 1 ) ) );
		$per_page = min( self::MAX_PER_PAGE, max( 1, $this->integer_param( $request, 'per_page', 10 ) ) );
		$brokers  = get_posts( $this->collection_query( $request, $page, $per_page ) );
		$items    = array();

		foreach ( $brokers as $broker ) {
			if ( ! $broker instanceof \WP_Post ) {
				continue;
			}

			$broker_data = $this->presenter->summary( $broker );

			if ( $broker_data instanceof WP_Error ) {
				return $broker_data;
			}

			$items[] = $broker_data;
		}

		return array(
			'items'      => $items,
			'pagination' => array(
				'page'     => $page,
				'per_page' => $per_page,
			),
		);
	}

	/** @return array<string, mixed>|WP_Error */
	public function get_single( WP_REST_Request $request ): array|WP_Error {
		$broker = $this->published_broker( $this->integer_param( $request, 'id', 0 ) );

		return $broker instanceof WP_Error ? $broker : $this->presenter->detail( $broker );
	}

	/** @return array<string, mixed>|WP_Error */
	public function compare( WP_REST_Request $request ): array|WP_Error {
		$broker_ids = $this->comparison_ids( $request->get_param( 'ids' ) );

		if ( $broker_ids instanceof WP_Error ) {
			return $broker_ids;
		}

		$brokers = get_posts(
			array(
				'post_type'              => BrokersModule::POST_TYPE,
				'post_status'            => 'publish',
				'post__in'               => $broker_ids,
				'posts_per_page'         => 3,
				'orderby'                => 'post__in',
				'no_found_rows'          => true,
				'suppress_filters'       => false,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => true,
			)
		);
		if ( count( $brokers ) !== count( $broker_ids ) ) {
			return $this->not_found();
		}

		$items = array();

		foreach ( $brokers as $broker ) {
			$broker_data = $this->presenter->comparison( $broker );

			if ( $broker_data instanceof WP_Error ) {
				return $broker_data;
			}

			$items[] = $broker_data;
		}

		return array(
			'items' => $items,
			'count' => count( $items ),
		);
	}

	public function is_positive_integer( mixed $value ): bool {
		return ( is_int( $value ) || ( is_string( $value ) && ctype_digit( $value ) ) ) && (int) $value > 0;
	}

	/** @return array<string, array<string, mixed>> */
	private function collection_arguments(): array {
		return array(
			'page'         => $this->integer_argument( 1, self::MAX_PAGE ),
			'per_page'     => $this->integer_argument( 10, self::MAX_PER_PAGE ),
			's'            => array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'broker_type'  => array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_key',
			),
			'jurisdiction' => array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_key',
			),
		);
	}

	/** @return array<string, mixed> */
	private function integer_argument( int $fallback, int $maximum ): array {
		return array(
			'type'              => 'integer',
			'default'           => $fallback,
			'minimum'           => 1,
			'maximum'           => $maximum,
			'sanitize_callback' => 'absint',
			'validate_callback' => array( $this, 'is_positive_integer' ),
		);
	}

	/** @return array<string, mixed> */
	private function collection_query( WP_REST_Request $request, int $page, int $per_page ): array {
		$query     = array(
			'post_type'              => BrokersModule::POST_TYPE,
			'post_status'            => 'publish',
			'posts_per_page'         => $per_page,
			'paged'                  => $page,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'suppress_filters'       => false,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => true,
		);
		$search    = $this->string_param( $request, 's' );
		$tax_query = $this->taxonomy_query( $request );

		if ( '' !== $search ) {
			$query['s'] = $search;
		}

		if ( array() !== $tax_query ) {
			$query['tax_query'] = $tax_query;
		}

		return $query;
	}

	/** @return array<string|int, array<string, mixed>|string> */
	private function taxonomy_query( WP_REST_Request $request ): array {
		$tax_query    = array( 'relation' => 'AND' );
		$broker_type  = sanitize_key( $this->string_param( $request, 'broker_type' ) );
		$jurisdiction = sanitize_key( $this->string_param( $request, 'jurisdiction' ) );

		if ( '' !== $broker_type ) {
			$tax_query[] = $this->taxonomy_clause( BrokersModule::TYPE_TAXONOMY, $broker_type );
		}

		if ( '' !== $jurisdiction ) {
			$tax_query[] = $this->taxonomy_clause( BrokersModule::JURISDICTION_TAXONOMY, $jurisdiction );
		}

		return 1 === count( $tax_query ) ? array() : $tax_query;
	}

	/** @return array{taxonomy: string, field: string, terms: list<string>} */
	private function taxonomy_clause( string $taxonomy, string $slug ): array {
		return array(
			'taxonomy' => $taxonomy,
			'field'    => 'slug',
			'terms'    => array( $slug ),
		);
	}

	/** @return list<int>|WP_Error */
	private function comparison_ids( mixed $ids ): array|WP_Error {
		if ( ! is_string( $ids ) ) {
			return $this->invalid_comparison();
		}

		$tokens = explode( ',', $ids );

		if ( array_filter( $tokens, static fn( string $token ): bool => ! ctype_digit( $token ) || (int) $token <= 0 ) ) {
			return $this->invalid_comparison();
		}

		$broker_ids = array_values( array_unique( array_map( 'intval', $tokens ) ) );

		return count( $broker_ids ) >= 2 && count( $broker_ids ) <= 3
			? $broker_ids
			: $this->invalid_comparison();
	}

	private function published_broker( int $broker_id ): \WP_Post|WP_Error {
		$broker = get_post( $broker_id );

		return $broker instanceof \WP_Post
			&& BrokersModule::POST_TYPE === $broker->post_type
			&& 'publish' === $broker->post_status
			? $broker
			: $this->not_found();
	}

	private function integer_param( WP_REST_Request $request, string $name, int $fallback ): int {
		$value = $request->get_param( $name );

		return is_numeric( $value ) ? (int) $value : $fallback;
	}

	private function string_param( WP_REST_Request $request, string $name ): string {
		$value = $request->get_param( $name );

		return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
	}

	private function invalid_comparison(): WP_Error {
		return new WP_Error(
			'alborsa_platform_broker_comparison_invalid',
			__( 'Choose two or three unique broker IDs.', 'alborsa-platform' ),
			array( 'status' => 400 )
		);
	}

	private function not_found(): WP_Error {
		return new WP_Error(
			'alborsa_platform_broker_not_found',
			__( 'Broker not found.', 'alborsa-platform' ),
			array( 'status' => 404 )
		);
	}
}

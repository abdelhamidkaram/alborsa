<?php
/**
 * Public asset REST endpoints.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\Assets;

use WP_Error;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

final class AssetRestController {
	public const NAMESPACE     = 'alborsa/v1';
	private const MAX_PER_PAGE = 100;
	private const MAX_PAGE     = 1000;

	private AssetPresenter $presenter;

	public function __construct( AssetPresenter $presenter ) {
		$this->presenter = $presenter;
	}

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/assets',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_collection' ),
				'permission_callback' => '__return_true',
				'args'                => $this->collection_arguments(),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/assets/(?P<id>[1-9][0-9]*)',
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

	/**
	 * @return array<string, mixed>|WP_Error
	 */
	public function get_collection( WP_REST_Request $request ): array|WP_Error {
		$page     = min( self::MAX_PAGE, max( 1, $this->integer_param( $request, 'page', 1 ) ) );
		$per_page = min( self::MAX_PER_PAGE, max( 1, $this->integer_param( $request, 'per_page', 10 ) ) );
		$assets   = get_posts( $this->query_arguments( $request, $page, $per_page ) );
		$items    = array();

		foreach ( $assets as $asset ) {
			if ( ! $asset instanceof \WP_Post ) {
				continue;
			}

			$summary = $this->presenter->summary( $asset );

			if ( $summary instanceof WP_Error ) {
				return $summary;
			}

			$items[] = $summary;
		}

		return array(
			'items'      => $items,
			'pagination' => array(
				'page'     => $page,
				'per_page' => $per_page,
			),
		);
	}

	/**
	 * @return array<string, mixed>|WP_Error
	 */
	public function get_single( WP_REST_Request $request ): array|WP_Error {
		$asset = get_post( $this->integer_param( $request, 'id', 0 ) );

		if ( ! $asset instanceof \WP_Post
			|| AssetsModule::POST_TYPE !== $asset->post_type
			|| 'publish' !== $asset->post_status
		) {
			return new WP_Error(
				'alborsa_platform_asset_not_found',
				__( 'Asset not found.', 'alborsa-platform' ),
				array( 'status' => 404 )
			);
		}

		return $this->presenter->detail( $asset );
	}

	public function is_positive_integer( mixed $value ): bool {
		return ( is_int( $value ) || ( is_string( $value ) && ctype_digit( $value ) ) ) && (int) $value > 0;
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function collection_arguments(): array {
		return array(
			'page'       => $this->integer_argument( 1, self::MAX_PAGE ),
			'per_page'   => $this->integer_argument( 10, self::MAX_PER_PAGE ),
			'search'     => array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'asset_type' => array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_key',
			),
			'exchange'   => array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_key',
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function integer_argument( int $fallback, ?int $maximum = null ): array {
		$argument = array(
			'type'              => 'integer',
			'default'           => $fallback,
			'minimum'           => 1,
			'sanitize_callback' => 'absint',
			'validate_callback' => array( $this, 'is_positive_integer' ),
		);

		if ( null !== $maximum ) {
			$argument['maximum'] = $maximum;
		}

		return $argument;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function query_arguments( WP_REST_Request $request, int $page, int $per_page ): array {
		$query_arguments = array(
			'post_type'              => AssetsModule::POST_TYPE,
			'post_status'            => 'publish',
			'posts_per_page'         => $per_page,
			'paged'                  => $page,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'suppress_filters'       => false,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => true,
		);
		$search          = $this->string_param( $request, 'search' );
		$tax_query       = $this->tax_query( $request );

		if ( '' !== $search ) {
			$query_arguments['s'] = $search;
		}

		if ( array() !== $tax_query ) {
			$query_arguments['tax_query'] = $tax_query;
		}

		return $query_arguments;
	}

	/**
	 * @return array<string|int, array<string, mixed>|string>
	 */
	private function tax_query( WP_REST_Request $request ): array {
		$tax_query  = array( 'relation' => 'AND' );
		$asset_type = sanitize_key( $this->string_param( $request, 'asset_type' ) );
		$exchange   = sanitize_key( $this->string_param( $request, 'exchange' ) );

		if ( '' !== $asset_type ) {
			$tax_query[] = $this->taxonomy_clause( AssetsModule::TYPE_TAXONOMY, $asset_type );
		}

		if ( '' !== $exchange ) {
			$tax_query[] = $this->taxonomy_clause( AssetsModule::EXCHANGE_TAXONOMY, $exchange );
		}

		return 1 === count( $tax_query ) ? array() : $tax_query;
	}

	/**
	 * @return array{taxonomy: string, field: string, terms: list<string>}
	 */
	private function taxonomy_clause( string $taxonomy, string $term_slug ): array {
		return array(
			'taxonomy' => $taxonomy,
			'field'    => 'slug',
			'terms'    => array( $term_slug ),
		);
	}

	private function integer_param( WP_REST_Request $request, string $name, int $fallback ): int {
		$value = $request->get_param( $name );

		return is_numeric( $value ) ? (int) $value : $fallback;
	}

	private function string_param( WP_REST_Request $request, string $name ): string {
		$value = $request->get_param( $name );

		return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
	}
}

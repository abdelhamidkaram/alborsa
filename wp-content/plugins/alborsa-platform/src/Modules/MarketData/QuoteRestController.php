<?php
/**
 * Public read-only latest quote endpoint.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\MarketData;

use Alborsa\Platform\Modules\Assets\AssetsModule;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

final class QuoteRestController {
	public const NAMESPACE = 'alborsa/v1';

	public function __construct( private QuoteReadService $quotes ) {
	}

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/assets/(?P<id>[1-9][0-9]*)/quote',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_quote' ),
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

	public function get_quote( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$asset_id = $this->asset_id( $request );
		$asset    = get_post( $asset_id );

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

		$quote = $this->quotes->find_for_asset( $asset_id );

		if ( $quote instanceof WP_Error ) {
			return $quote;
		}

		$response = new WP_REST_Response( $quote->data(), 200 );
		$response->header(
			'Cache-Control',
			'public, max-age=' . $quote->cache_ttl() . ', stale-while-revalidate=60'
		);

		return $response;
	}

	public function is_positive_integer( mixed $value ): bool {
		return ( is_int( $value ) || ( is_string( $value ) && ctype_digit( $value ) ) ) && (int) $value > 0;
	}

	private function asset_id( WP_REST_Request $request ): int {
		$value = $request->get_param( 'id' );

		return is_numeric( $value ) ? (int) $value : 0;
	}
}

<?php
/**
 * Stateless public REST interface for financial estimates.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\FinancialTools;

use WP_Error;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

final class ToolRestController {
	public const NAMESPACE = 'alborsa/v1';

	public function __construct( private CalculatorRegistry $registry ) {
	}

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/tools',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'catalog' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/tools/(?P<slug>[a-z0-9-]+)/calculate',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'calculate' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'slug' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);
	}

	/** @return array<string, mixed> */
	public function catalog(): array {
		return array( 'items' => $this->registry->catalog() );
	}

	/** @return array<string, mixed>|WP_Error */
	public function calculate( WP_REST_Request $request ): array|WP_Error {
		$slug   = sanitize_key( (string) $request->get_param( 'slug' ) );
		$inputs = $request->get_json_params();

		if ( count( $inputs ) > 12 ) {
			return new WP_Error(
				'alborsa_platform_tool_invalid_request',
				__( 'Calculator inputs must be supplied as a small JSON object.', 'alborsa-platform' ),
				array( 'status' => 400 )
			);
		}

		$result = $this->registry->calculate( $slug, $inputs );

		if ( $result instanceof WP_Error ) {
			return $result;
		}

		return array(
			'tool'          => $slug,
			'estimate_only' => true,
			'result'        => $result,
			'assumptions'   => $this->assumptions( $slug ),
			'disclaimer'    => __( 'Educational estimate only. It is not financial, investment, tax, or religious advice.', 'alborsa-platform' ),
		);
	}

	/** @return list<string> */
	private function assumptions( string $slug ): array {
		foreach ( $this->registry->catalog() as $definition ) {
			if ( ( $definition['slug'] ?? null ) === $slug && isset( $definition['assumptions'] ) && is_array( $definition['assumptions'] ) ) {
				return array_values( array_filter( $definition['assumptions'], 'is_string' ) );
			}
		}

		return array();
	}
}

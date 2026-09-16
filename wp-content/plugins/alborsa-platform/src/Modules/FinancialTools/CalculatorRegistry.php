<?php
/**
 * Financial calculator catalog and dispatcher.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\FinancialTools;

use WP_Error;

defined( 'ABSPATH' ) || exit;

final class CalculatorRegistry {
	/** @var array<string, Calculator> */
	private array $calculators = array();

	/** @param list<Calculator> $calculators Available calculators. */
	public function __construct( array $calculators ) {
		foreach ( $calculators as $calculator ) {
			$this->calculators[ $calculator->slug() ] = $calculator;
		}
	}

	/** @return list<array<string, mixed>> */
	public function catalog(): array {
		$definitions = array_map(
			static fn ( Calculator $calculator ): array => $calculator->definition(),
			array_values( $this->calculators )
		);

		return array_merge( $definitions, $this->unavailable_definitions() );
	}

	/**
	 * @param array<string, mixed> $inputs Calculator inputs.
	 * @return array<string, mixed>|WP_Error
	 */
	public function calculate( string $slug, array $inputs ): array|WP_Error {
		if ( isset( $this->calculators[ $slug ] ) ) {
			return $this->calculators[ $slug ]->calculate( $inputs );
		}

		$blocked = array(
			'currency-converter' => 'D-008',
			'zakat'              => 'D-006',
		);

		if ( isset( $blocked[ $slug ] ) ) {
			return new WP_Error(
				'alborsa_platform_tool_unavailable',
				__( 'This calculator is unavailable until its data or methodology decision is approved.', 'alborsa-platform' ),
				array(
					'status'   => 409,
					'decision' => $blocked[ $slug ],
				)
			);
		}

		return new WP_Error(
			'alborsa_platform_tool_not_found',
			__( 'Calculator not found.', 'alborsa-platform' ),
			array( 'status' => 404 )
		);
	}

	/** @return list<array<string, mixed>> */
	private function unavailable_definitions(): array {
		return array(
			array(
				'slug'        => 'currency-converter',
				'label'       => __( 'Currency converter', 'alborsa-platform' ),
				'status'      => 'provider_pending',
				'decision'    => 'D-008',
				'inputs'      => array(),
				'assumptions' => array(),
			),
			array(
				'slug'        => 'zakat',
				'label'       => __( 'Investment zakat calculator', 'alborsa-platform' ),
				'status'      => 'methodology_pending',
				'decision'    => 'D-006',
				'inputs'      => array(),
				'assumptions' => array(),
			),
		);
	}
}

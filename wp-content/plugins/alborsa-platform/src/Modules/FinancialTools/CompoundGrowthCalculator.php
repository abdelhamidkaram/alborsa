<?php
/**
 * Monthly contribution compound growth estimate.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\FinancialTools;

use WP_Error;

defined( 'ABSPATH' ) || exit;

final class CompoundGrowthCalculator implements Calculator {
	public function __construct( private CalculationInput $input ) {
	}

	public function slug(): string {
		return 'compound-growth';
	}

	/** @return array<string, mixed> */
	public function definition(): array {
		return array(
			'slug'        => $this->slug(),
			'label'       => __( 'Compound investment growth calculator', 'alborsa-platform' ),
			'status'      => 'available',
			'inputs'      => array( 'initial_amount', 'monthly_contribution', 'annual_rate_percent', 'years' ),
			'assumptions' => array(
				__( 'Returns compound monthly at a constant nominal annual rate.', 'alborsa-platform' ),
				__( 'Monthly contributions are added at the end of each month.', 'alborsa-platform' ),
				__( 'Taxes, fees, inflation, and market variability are excluded.', 'alborsa-platform' ),
			),
		);
	}

	/** @param array<string, mixed> $inputs Calculator inputs. */
	public function calculate( array $inputs ): array|WP_Error {
		$initial = $this->input->decimal( $inputs, 'initial_amount', 0.0, 1_000_000_000_000.0 );
		if ( $initial instanceof WP_Error ) {
			return $initial;
		}

		$contribution = $this->input->decimal( $inputs, 'monthly_contribution', 0.0, 1_000_000_000.0 );
		if ( $contribution instanceof WP_Error ) {
			return $contribution;
		}

		$annual_rate = $this->input->decimal( $inputs, 'annual_rate_percent', -99.99, 100.0 );
		if ( $annual_rate instanceof WP_Error ) {
			return $annual_rate;
		}

		$years = $this->input->integer( $inputs, 'years', 1, 100 );
		if ( $years instanceof WP_Error ) {
			return $years;
		}

		$months       = $years * 12;
		$monthly_rate = $annual_rate / 1200;
		$growth       = $this->growth_factor( $monthly_rate, $months );
		$future_value = $initial * $growth + $this->contribution_value( $contribution, $monthly_rate, $months, $growth );
		$contributed  = $initial + $contribution * $months;

		return array(
			'total_contributions' => $this->input->format( $contributed, 2 ),
			'estimated_growth'    => $this->input->format( $future_value - $contributed, 2 ),
			'future_value'        => $this->input->format( $future_value, 2 ),
		);
	}

	private function growth_factor( float $monthly_rate, int $months ): float {
		return ( 1 + $monthly_rate ) ** $months;
	}

	private function contribution_value(
		float $contribution,
		float $monthly_rate,
		int $months,
		float $growth
	): float {
		return 0.0 === $monthly_rate
			? $contribution * $months
			: $contribution * ( ( $growth - 1 ) / $monthly_rate );
	}
}

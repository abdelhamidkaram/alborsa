<?php
/**
 * Pip value estimate from explicit conversion inputs.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\FinancialTools;

use WP_Error;

defined( 'ABSPATH' ) || exit;

final class PipValueCalculator implements Calculator {
	public function __construct( private CalculationInput $input ) {
	}

	public function slug(): string {
		return 'pip-value';
	}

	/** @return array<string, mixed> */
	public function definition(): array {
		return array(
			'slug'        => $this->slug(),
			'label'       => __( 'Pip value calculator', 'alborsa-platform' ),
			'status'      => 'available',
			'inputs'      => array( 'position_units', 'pip_size', 'quote_to_account_rate' ),
			'assumptions' => array(
				__( 'Use a conversion rate of 1 when the quote currency equals the account currency.', 'alborsa-platform' ),
				__( 'The calculator does not fetch or infer exchange rates.', 'alborsa-platform' ),
			),
		);
	}

	/** @param array<string, mixed> $inputs Calculator inputs. */
	public function calculate( array $inputs ): array|WP_Error {
		$units = $this->input->decimal( $inputs, 'position_units', 0.00000001, 1_000_000_000_000.0 );
		if ( $units instanceof WP_Error ) {
			return $units;
		}

		$pip = $this->input->decimal( $inputs, 'pip_size', 0.00000001, 1.0 );
		if ( $pip instanceof WP_Error ) {
			return $pip;
		}

		$rate = $this->input->decimal( $inputs, 'quote_to_account_rate', 0.00000001, 1_000_000.0 );
		if ( $rate instanceof WP_Error ) {
			return $rate;
		}

		return array( 'pip_value' => $this->input->format( $units * $pip * $rate ) );
	}
}

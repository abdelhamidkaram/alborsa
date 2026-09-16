<?php
/**
 * Risk-based position size estimate.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\FinancialTools;

use WP_Error;

defined( 'ABSPATH' ) || exit;

final class PositionSizeCalculator implements Calculator {
	public function __construct( private CalculationInput $input ) {
	}

	public function slug(): string {
		return 'position-size';
	}

	/** @return array<string, mixed> */
	public function definition(): array {
		return array(
			'slug'        => $this->slug(),
			'label'       => __( 'Position size calculator', 'alborsa-platform' ),
			'status'      => 'available',
			'inputs'      => array( 'account_balance', 'risk_percent', 'stop_loss_pips', 'pip_value_per_unit' ),
			'assumptions' => array(
				__( 'The pip value per unit must already be expressed in the account currency.', 'alborsa-platform' ),
				__( 'The estimate excludes slippage, gaps, commissions, and margin constraints.', 'alborsa-platform' ),
			),
		);
	}

	/** @param array<string, mixed> $inputs Calculator inputs. */
	public function calculate( array $inputs ): array|WP_Error {
		$balance = $this->input->decimal( $inputs, 'account_balance', 0.01, 1_000_000_000_000.0 );
		if ( $balance instanceof WP_Error ) {
			return $balance;
		}

		$risk = $this->input->decimal( $inputs, 'risk_percent', 0.0001, 100.0 );
		if ( $risk instanceof WP_Error ) {
			return $risk;
		}

		$stop = $this->input->decimal( $inputs, 'stop_loss_pips', 0.0001, 1_000_000.0 );
		if ( $stop instanceof WP_Error ) {
			return $stop;
		}

		$pip_unit = $this->input->decimal( $inputs, 'pip_value_per_unit', 0.00000001, 1_000_000.0 );
		if ( $pip_unit instanceof WP_Error ) {
			return $pip_unit;
		}

		$risk_amount   = $balance * ( $risk / 100 );
		$position_size = $risk_amount / ( $stop * $pip_unit );

		return array(
			'risk_amount'    => $this->input->format( $risk_amount ),
			'position_units' => $this->input->format( $position_size ),
		);
	}
}

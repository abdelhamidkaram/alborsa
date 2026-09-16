<?php
/**
 * Long/short position profit and loss estimate.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\FinancialTools;

use WP_Error;

defined( 'ABSPATH' ) || exit;

final class ProfitLossCalculator implements Calculator {
	public function __construct( private CalculationInput $input ) {
	}

	public function slug(): string {
		return 'profit-loss';
	}

	/** @return array<string, mixed> */
	public function definition(): array {
		return array(
			'slug'        => $this->slug(),
			'label'       => __( 'Profit and loss calculator', 'alborsa-platform' ),
			'status'      => 'available',
			'inputs'      => array( 'entry_price', 'exit_price', 'quantity', 'fees', 'side' ),
			'assumptions' => array(
				__( 'Fees are entered as a total amount and deducted once.', 'alborsa-platform' ),
				__( 'Taxes, financing costs, slippage, and currency conversion are excluded.', 'alborsa-platform' ),
			),
		);
	}

	/** @param array<string, mixed> $inputs Calculator inputs. */
	public function calculate( array $inputs ): array|WP_Error {
		$entry_price = $this->input->decimal( $inputs, 'entry_price', 0.00000001, 1_000_000_000_000.0 );
		if ( $entry_price instanceof WP_Error ) {
			return $entry_price;
		}

		$exit_price = $this->input->decimal( $inputs, 'exit_price', 0.00000001, 1_000_000_000_000.0 );
		if ( $exit_price instanceof WP_Error ) {
			return $exit_price;
		}

		$quantity = $this->input->decimal( $inputs, 'quantity', 0.00000001, 1_000_000_000_000.0 );
		if ( $quantity instanceof WP_Error ) {
			return $quantity;
		}

		$fees = $this->input->decimal( $inputs, 'fees', 0.0, 1_000_000_000_000.0 );
		if ( $fees instanceof WP_Error ) {
			return $fees;
		}

		$side = $this->input->choice( $inputs, 'side', array( 'long', 'short' ) );
		if ( $side instanceof WP_Error ) {
			return $side;
		}

		$price_change = 'long' === $side ? $exit_price - $entry_price : $entry_price - $exit_price;
		$gross        = $price_change * $quantity;

		return array(
			'gross_profit_loss' => $this->input->format( $gross ),
			'net_profit_loss'   => $this->input->format( $gross - $fees ),
			'fees'              => $this->input->format( $fees ),
			'side'              => $side,
		);
	}
}

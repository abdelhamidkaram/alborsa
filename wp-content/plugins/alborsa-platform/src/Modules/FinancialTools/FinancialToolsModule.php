<?php
/**
 * Financial calculators module.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\FinancialTools;

use Alborsa\Platform\Foundation\Module;

defined( 'ABSPATH' ) || exit;

final class FinancialToolsModule implements Module {
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	public function register_rest_routes(): void {
		$input    = new CalculationInput();
		$registry = new CalculatorRegistry(
			array(
				new ProfitLossCalculator( $input ),
				new PositionSizeCalculator( $input ),
				new PipValueCalculator( $input ),
				new CompoundGrowthCalculator( $input ),
			)
		);

		( new ToolRestController( $registry ) )->register_routes();
	}
}

<?php
/**
 * Financial tool REST boundary tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\FinancialTools;

use Alborsa\Platform\Modules\FinancialTools\CalculationInput;
use Alborsa\Platform\Modules\FinancialTools\CalculatorRegistry;
use Alborsa\Platform\Modules\FinancialTools\PipValueCalculator;
use Alborsa\Platform\Modules\FinancialTools\ToolRestController;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;

final class ToolRestControllerTest extends UnitTestCase {
	public function test_registration_exposes_only_catalog_and_calculation_routes(): void {
		$routes = array();
		Functions\when( 'register_rest_route' )->alias(
			static function ( string $route_namespace, string $route, array $arguments ) use ( &$routes ): bool {
				$routes[ $route ] = array(
					'namespace' => $route_namespace,
					'arguments' => $arguments,
				);

				return true;
			}
		);

		$this->controller()->register_routes();

		self::assertSame( array( '/tools', '/tools/(?P<slug>[a-z0-9-]+)/calculate' ), array_keys( $routes ) );
		self::assertSame( 'GET', $routes['/tools']['arguments']['methods'] );
		self::assertSame( 'POST', $routes['/tools/(?P<slug>[a-z0-9-]+)/calculate']['arguments']['methods'] );
	}

	public function test_calculation_response_is_explicitly_an_estimate(): void {
		Functions\when( 'sanitize_key' )->returnArg();

		$response = $this->controller()->calculate(
			new \WP_REST_Request(
				array(
					'slug'  => 'pip-value',
					'_json' => array(
						'position_units'        => '100000',
						'pip_size'              => '0.0001',
						'quote_to_account_rate' => '1',
					),
				)
			)
		);

		self::assertIsArray( $response );
		self::assertTrue( $response['estimate_only'] );
		self::assertSame( '10', $response['result']['pip_value'] );
		self::assertNotEmpty( $response['assumptions'] );
		self::assertNotEmpty( $response['disclaimer'] );
	}

	public function test_calculation_rejects_missing_json_body(): void {
		Functions\when( 'sanitize_key' )->returnArg();

		$response = $this->controller()->calculate( new \WP_REST_Request( array( 'slug' => 'pip-value' ) ) );

		self::assertTrue( is_wp_error( $response ) );
		self::assertSame( 400, $response->get_error_data()['status'] );
	}

	private function controller(): ToolRestController {
		$registry = new CalculatorRegistry( array( new PipValueCalculator( new CalculationInput() ) ) );

		return new ToolRestController( $registry );
	}
}

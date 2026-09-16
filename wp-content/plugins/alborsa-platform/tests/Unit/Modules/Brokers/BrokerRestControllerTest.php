<?php
/**
 * Broker REST controller tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\Brokers;

use Alborsa\Platform\Modules\Brokers\BrokerMeta;
use Alborsa\Platform\Modules\Brokers\BrokerPresenter;
use Alborsa\Platform\Modules\Brokers\BrokerRestController;
use Alborsa\Platform\Modules\Brokers\BrokerReviewRepository;
use Alborsa\Platform\Tests\Support\WpdbDouble;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;

final class BrokerRestControllerTest extends UnitTestCase {
	public function test_registration_exposes_read_only_broker_and_comparison_routes(): void {
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

		self::assertSame(
			array( '/brokers', '/brokers/compare', '/brokers/(?P<id>[1-9][0-9]*)' ),
			array_keys( $routes )
		);
		self::assertSame( 'GET', $routes['/brokers']['arguments']['methods'] );
		self::assertSame( 'GET', $routes['/brokers/compare']['arguments']['methods'] );
		self::assertArrayNotHasKey( '/brokers/(?P<id>[1-9][0-9]*)/reviews', $routes );
	}

	public function test_comparison_rejects_malformed_or_out_of_range_id_sets_before_query(): void {
		Functions\expect( 'get_posts' )->never();

		$response = $this->controller()->compare( new \WP_REST_Request( array( 'ids' => '1,2x,3,4' ) ) );

		self::assertTrue( is_wp_error( $response ) );
		self::assertSame( 'alborsa_platform_broker_comparison_invalid', $response->get_error_code() );
		self::assertSame( array( 'status' => 400 ), $response->get_error_data() );
	}

	private function controller(): BrokerRestController {
		$presenter = new BrokerPresenter( new BrokerMeta(), new BrokerReviewRepository( new WpdbDouble() ) );

		return new BrokerRestController( $presenter );
	}
}

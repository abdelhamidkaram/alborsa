<?php
/**
 * Brokers module tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\Brokers;

use Alborsa\Platform\Modules\Brokers\BrokersModule;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;

final class BrokersModuleTest extends UnitTestCase {
	public function test_registration_defers_content_model_and_rest_routes(): void {
		( new BrokersModule() )->register();

		self::assertSame( 10, has_action( 'init', BrokersModule::class . '->initialize()' ) );
		self::assertSame( 10, has_action( 'rest_api_init', BrokersModule::class . '->register_rest_routes()' ) );
	}

	public function test_initialization_registers_approved_broker_url_and_private_taxonomy_archives(): void {
		$post_type  = array();
		$taxonomies = array();
		Functions\when( 'register_post_type' )->alias(
			static function ( string $name, array $arguments ) use ( &$post_type ): void {
				$post_type = array(
					'name'      => $name,
					'arguments' => $arguments,
				);
			}
		);
		Functions\when( 'register_taxonomy' )->alias(
			static function ( string $name, array $types, array $arguments ) use ( &$taxonomies ): void {
				unset( $types );
				$taxonomies[ $name ] = $arguments;
			}
		);
		Functions\when( 'register_post_meta' )->justReturn( true );
		Functions\when( 'register_meta' )->justReturn( true );

		( new BrokersModule() )->initialize();

		self::assertSame( BrokersModule::POST_TYPE, $post_type['name'] );
		self::assertSame( 'brokers', $post_type['arguments']['has_archive'] );
		self::assertSame( 'brokers', $post_type['arguments']['rewrite']['slug'] );
		self::assertSame( array( BrokersModule::TYPE_TAXONOMY, BrokersModule::JURISDICTION_TAXONOMY ), array_keys( $taxonomies ) );
		self::assertFalse( $taxonomies[ BrokersModule::TYPE_TAXONOMY ]['publicly_queryable'] );
		self::assertFalse( $taxonomies[ BrokersModule::JURISDICTION_TAXONOMY ]['rewrite'] );
	}
}

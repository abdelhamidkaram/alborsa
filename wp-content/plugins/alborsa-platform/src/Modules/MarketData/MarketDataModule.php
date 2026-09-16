<?php
/**
 * Market data runtime composition.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\MarketData;

use Alborsa\Platform\Foundation\Module;

defined( 'ABSPATH' ) || exit;

final class MarketDataModule implements Module {
	public function register(): void {
		add_action( 'init', array( $this, 'initialize' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	public function initialize(): void {
		$providers = QuoteProviderRegistry::from_wordpress();
		$health    = new ProviderHealthStore();

		( new MarketDataHealth( $providers, $health ) )->register();
		$this->register_cli( $providers, $health );
	}

	public function register_rest_routes(): void {
		$quotes = QuoteReadService::from_wordpress();

		if ( is_wp_error( $quotes ) ) {
			return;
		}

		( new QuoteRestController( $quotes ) )->register_routes();
	}

	private function register_cli( QuoteProviderRegistry $providers, ProviderHealthStore $health ): void {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! class_exists( 'WP_CLI' ) ) {
			return;
		}

		global $wpdb;

		if ( ! $wpdb instanceof \wpdb ) {
			return;
		}

		$mappings = new AssetQuoteMappingRepository();
		$sync     = new QuoteSyncService( new QuoteRepository( $wpdb ), new QuoteCache(), $health );

		\WP_CLI::add_command(
			'alborsa market sync',
			new QuoteSyncCommand( $providers, $mappings, $sync ),
			array(
				'shortdesc' => __( 'Synchronizes latest quotes from a configured provider.', 'alborsa-platform' ),
				'synopsis'  => array(
					array(
						'type'        => 'assoc',
						'name'        => 'provider',
						'description' => __( 'Registered quote provider key.', 'alborsa-platform' ),
						'optional'    => false,
					),
				),
			)
		);
	}
}

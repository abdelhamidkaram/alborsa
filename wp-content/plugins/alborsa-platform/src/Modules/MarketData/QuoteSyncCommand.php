<?php
/**
 * Server-cron friendly WP-CLI quote synchronization command.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\MarketData;

defined( 'ABSPATH' ) || exit;

final class QuoteSyncCommand {
	public function __construct(
		private QuoteProviderRegistry $providers,
		private AssetQuoteMappingRepository $mappings,
		private QuoteSyncService $sync
	) {
	}

	/**
	 * @param list<string>          $arguments Positional WP-CLI arguments.
	 * @param array<string, string> $named_arguments Named WP-CLI arguments.
	 */
	public function __invoke( array $arguments, array $named_arguments ): void {
		unset( $arguments );
		$provider_key = sanitize_key( $named_arguments['provider'] ?? '' );

		if ( '' === $provider_key ) {
			\WP_CLI::error( __( 'Pass a configured provider with --provider=<key>.', 'alborsa-platform' ) );
			return;
		}

		$provider = $this->providers->get( $provider_key );

		if ( is_wp_error( $provider ) ) {
			\WP_CLI::error( $provider->get_error_message() );
			return;
		}

		$assets_saved = $this->sync_batches( $provider );
		if ( is_wp_error( $assets_saved ) ) {
			\WP_CLI::error( $assets_saved->get_error_message() );
			return;
		}

		if ( $assets_saved <= 0 ) {
			\WP_CLI::error( __( 'No published assets have an enabled quote mapping for this provider.', 'alborsa-platform' ) );
			return;
		}

		\WP_CLI::success(
			sprintf(
				/* translators: %d: number of assets whose latest quote was saved. */
				_n( 'Saved a quote for %d asset.', 'Saved quotes for %d assets.', $assets_saved, 'alborsa-platform' ),
				$assets_saved
			)
		);
	}

	private function sync_batches( QuoteProviderInterface $provider ): int|\WP_Error {
		$assets_saved = 0;

		foreach ( $this->mappings->batches_for_provider( $provider->key() ) as $mappings ) {
			$report = $this->sync->sync( $provider, $mappings );

			if ( is_wp_error( $report ) ) {
				return $report;
			}

			$assets_saved += $report['assets_saved'];
		}

		return $assets_saved;
	}
}

<?php
/**
 * Provider-neutral quote fetching contract.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\MarketData;

use WP_Error;

defined( 'ABSPATH' ) || exit;

interface QuoteProviderInterface {
	public function key(): string;

	/**
	 * @param list<string> $provider_symbols Provider-specific symbols.
	 * @return list<NormalizedQuote>|WP_Error Quotes in request order.
	 */
	public function fetch_quotes( array $provider_symbols ): array|WP_Error;
}

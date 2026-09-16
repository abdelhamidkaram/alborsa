<?php
/**
 * Public latest-quote read model with cache policy.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\MarketData;

defined( 'ABSPATH' ) || exit;

final class QuoteReadResult {
	/** @param array<string, mixed> $data Public quote fields. */
	public function __construct(
		private readonly array $data,
		private readonly int $cache_ttl
	) {
	}

	/** @return array<string, mixed> */
	public function data(): array {
		return $this->data;
	}

	public function cache_ttl(): int {
		return $this->cache_ttl;
	}
}

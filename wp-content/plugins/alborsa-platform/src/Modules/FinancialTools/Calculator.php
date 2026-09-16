<?php
/**
 * Contract for stateless financial estimates.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\FinancialTools;

use WP_Error;

defined( 'ABSPATH' ) || exit;

interface Calculator {
	public function slug(): string;

	/** @return array<string, mixed> */
	public function definition(): array;

	/**
	 * @param array<string, mixed> $inputs Validated request container.
	 * @return array<string, mixed>|WP_Error
	 */
	public function calculate( array $inputs ): array|WP_Error;
}

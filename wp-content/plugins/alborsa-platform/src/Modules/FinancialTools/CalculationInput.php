<?php
/**
 * Shared bounded numeric input rules for financial calculators.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\FinancialTools;

use WP_Error;

defined( 'ABSPATH' ) || exit;

final class CalculationInput {
	/** @param array<string, mixed> $inputs Calculator inputs. */
	public function decimal( array $inputs, string $field, float $minimum, float $maximum ): float|WP_Error {
		$value = $inputs[ $field ] ?? null;

		if ( ! is_string( $value ) && ! is_int( $value ) ) {
			return $this->error( $field );
		}

		$decimal = trim( (string) $value );

		if ( ! preg_match( '/\A-?\d{1,15}(?:\.\d{1,8})?\z/', $decimal ) ) {
			return $this->error( $field );
		}

		$number = (float) $decimal;

		return $number >= $minimum && $number <= $maximum ? $number : $this->error( $field );
	}

	/** @param array<string, mixed> $inputs Calculator inputs. */
	public function integer( array $inputs, string $field, int $minimum, int $maximum ): int|WP_Error {
		$value = $inputs[ $field ] ?? null;

		if ( ! is_int( $value ) && ( ! is_string( $value ) || ! ctype_digit( $value ) ) ) {
			return $this->error( $field );
		}

		$integer = (int) $value;

		return $integer >= $minimum && $integer <= $maximum ? $integer : $this->error( $field );
	}

	/**
	 * @param array<string, mixed> $inputs Calculator inputs.
	 * @param list<string>         $choices Allowed values.
	 */
	public function choice( array $inputs, string $field, array $choices ): string|WP_Error {
		$value = $inputs[ $field ] ?? null;

		return is_string( $value ) && in_array( $value, $choices, true ) ? $value : $this->error( $field );
	}

	public function format( float $number, int $scale = 4 ): string {
		$formatted = number_format( $number, $scale, '.', '' );
		$formatted = rtrim( rtrim( $formatted, '0' ), '.' );

		return '-0' === $formatted || '' === $formatted ? '0' : $formatted;
	}

	private function error( string $field ): WP_Error {
		return new WP_Error(
			'alborsa_platform_tool_invalid_input',
			sprintf(
				/* translators: %s: calculator input field name. */
				__( 'The calculator input %s is invalid or outside its supported range.', 'alborsa-platform' ),
				$field
			),
			array(
				'status' => 400,
				'field'  => $field,
			)
		);
	}
}

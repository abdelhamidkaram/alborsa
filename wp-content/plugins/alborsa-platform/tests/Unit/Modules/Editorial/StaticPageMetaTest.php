<?php
/**
 * Static page metadata tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\Editorial;

use Alborsa\Platform\Modules\Editorial\StaticPageMeta;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\DataProvider;

final class StaticPageMetaTest extends UnitTestCase {
	public function test_registers_boolean_page_field_for_authorized_editors(): void {
		$registration = array();
		Functions\when( 'register_post_meta' )->alias(
			static function ( string $post_type, string $meta_key, array $arguments ) use ( &$registration ): bool {
				$registration = array(
					'post_type' => $post_type,
					'meta_key'  => $meta_key,
					'arguments' => $arguments,
				);

				return true;
			}
		);

		$meta = new StaticPageMeta();
		$meta->register();

		self::assertSame( 'page', $registration['post_type'] );
		self::assertSame( StaticPageMeta::ENABLED, $registration['meta_key'] );
		self::assertSame( 'boolean', $registration['arguments']['type'] );
		self::assertTrue( $registration['arguments']['single'] );
		self::assertTrue( $registration['arguments']['show_in_rest'] );
		self::assertFalse( $registration['arguments']['default'] );
	}

	#[DataProvider( 'boolean_cases' )]
	public function test_boolean_sanitizer_accepts_only_explicit_enabled_values( mixed $raw_value, bool $expected ): void {
		self::assertSame( $expected, ( new StaticPageMeta() )->sanitize_boolean( $raw_value ) );
	}

	/** @return iterable<string, array{mixed, bool}> */
	public static function boolean_cases(): iterable {
		yield 'boolean true' => array( true, true );
		yield 'integer one' => array( 1, true );
		yield 'string one' => array( '1', true );
		yield 'checkbox on' => array( 'on', true );
		yield 'boolean false' => array( false, false );
		yield 'unsupported yes' => array( 'yes', false );
		yield 'non-scalar array' => array( array( '1' ), false );
	}

	public function test_edit_authorization_uses_the_page_capability(): void {
		Functions\expect( 'current_user_can' )->once()->with( 'edit_post', 42 )->andReturn( true );

		self::assertTrue( ( new StaticPageMeta() )->can_edit( false, StaticPageMeta::ENABLED, 42 ) );
	}
}

<?php
/**
 * Broker review metadata tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\Brokers;

use Alborsa\Platform\Modules\Brokers\BrokerReviewMeta;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\DataProvider;

final class BrokerReviewMetaTest extends UnitTestCase {
	#[DataProvider( 'review_field_cases' )]
	public function test_review_fields_reject_unsupported_values( string $method, mixed $input, mixed $expected ): void {
		Functions\when( 'sanitize_key' )->alias(
			static fn( string $key ): string => preg_replace( '/[^a-z0-9_-]/', '', strtolower( $key ) ) ?? ''
		);

		self::assertSame( $expected, ( new BrokerReviewMeta() )->{$method}( $input ) );
	}

	/** @return iterable<string, array{string, mixed, mixed}> */
	public static function review_field_cases(): iterable {
		yield 'valid rating' => array( 'sanitize_rating', '5', 5 );
		yield 'out-of-range rating' => array( 'sanitize_rating', 6, 0 );
		yield 'known verification state' => array( 'sanitize_verification_status', 'email_verified', 'email_verified' );
		yield 'unknown verification state' => array( 'sanitize_verification_status', 'partner_claimed', 'unverified' );
		yield 'valid experience date' => array( 'sanitize_date', '2026-09-16', '2026-09-16' );
		yield 'invalid experience date' => array( 'sanitize_date', '2026-02-30', '' );
	}

	public function test_comment_meta_is_moderator_only_and_not_exposed_in_rest(): void {
		$registrations = array();
		Functions\when( 'register_meta' )->alias(
			static function ( string $object_type, string $meta_key, array $arguments ) use ( &$registrations ): bool {
				self::assertSame( 'comment', $object_type );
				$registrations[ $meta_key ] = $arguments;

				return true;
			}
		);

		( new BrokerReviewMeta() )->register();

		self::assertCount( 6, $registrations );
		self::assertFalse( $registrations[ BrokerReviewMeta::RATING ]['show_in_rest'] );
		self::assertIsCallable( $registrations[ BrokerReviewMeta::MODERATION_NOTE ]['auth_callback'] );
	}
}

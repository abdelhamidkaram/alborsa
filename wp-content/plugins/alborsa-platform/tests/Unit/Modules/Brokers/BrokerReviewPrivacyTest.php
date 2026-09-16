<?php
/**
 * Broker review privacy integration tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\Brokers;

use Alborsa\Platform\Modules\Brokers\BrokerReviewMeta;
use Alborsa\Platform\Modules\Brokers\BrokerReviewPrivacy;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;

final class BrokerReviewPrivacyTest extends UnitTestCase {
	public function test_exporter_precedes_core_and_exports_protected_review_metadata(): void {
		Functions\when( 'sanitize_email' )->returnArg();
		Functions\when( 'get_comments' )->justReturn( array( $this->comment() ) );
		Functions\when( 'get_comment_meta' )->alias(
			static fn( int $comment_id, string $meta_key ): string => 15 === $comment_id && BrokerReviewMeta::RATING === $meta_key ? '5' : ''
		);
		$privacy  = new BrokerReviewPrivacy();
		$registry = $privacy->register_exporter( array( 'wordpress-comments' => array() ) );
		$export   = $privacy->export( 'person@example.test' );

		self::assertSame( 'alborsa-platform-broker-reviews', array_key_first( $registry ) );
		self::assertTrue( $export['done'] );
		self::assertSame( 'comment-15', $export['data'][0]['item_id'] );
		self::assertSame( '5', $export['data'][0]['data'][0]['value'] );
	}

	public function test_eraser_removes_all_custom_review_metadata_before_core_anonymization(): void {
		$deleted = array();
		Functions\when( 'sanitize_email' )->returnArg();
		Functions\when( 'get_comments' )->justReturn( array( $this->comment() ) );
		Functions\when( 'delete_comment_meta' )->alias(
			static function ( int $comment_id, string $meta_key ) use ( &$deleted ): bool {
				$deleted[] = array( $comment_id, $meta_key );

				return true;
			}
		);

		$result = ( new BrokerReviewPrivacy() )->erase( 'person@example.test' );

		self::assertTrue( $result['items_removed'] );
		self::assertFalse( $result['items_retained'] );
		self::assertCount( 6, $deleted );
	}

	private function comment(): \WP_Comment {
		return new \WP_Comment(
			(object) array(
				'comment_ID'       => 15,
				'comment_author'   => 'Person',
				'comment_content'  => 'Review',
				'comment_date_gmt' => '2026-09-16 10:00:00',
			)
		);
	}
}

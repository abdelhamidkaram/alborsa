<?php
/**
 * Broker review repository tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\Brokers;

use Alborsa\Platform\Modules\Brokers\BrokerReviewMeta;
use Alborsa\Platform\Modules\Brokers\BrokerReviewRepository;
use Alborsa\Platform\Tests\Support\WpdbDouble;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;

final class BrokerReviewRepositoryTest extends UnitTestCase {
	public function test_summary_excludes_rejected_reviews_and_keeps_verification_badge_closed(): void {
		$wpdb = new class() extends WpdbDouble {
			public function get_results( string $query, string|int $output = 'OBJECT' ): ?array {
				parent::get_results( $query, $output );

				return array(
					array(
						'review_count'   => '12',
						'average_rating' => '4.25',
					),
				);
			}
		};

		$summary = ( new BrokerReviewRepository( $wpdb ) )->summary( 50 );

		self::assertFalse( is_wp_error( $summary ) );
		self::assertSame( 12, $summary['count'] );
		self::assertSame( '4.25', $summary['average'] );
		self::assertSame( 0, $summary['verified_count'] );
		self::assertSame( 'pending', $summary['verification_policy'] );
		self::assertStringContainsString( BrokerReviewMeta::REJECTED, (string) $wpdb->last_query );
	}

	public function test_latest_public_reviews_omit_rejected_entries_and_contact_data(): void {
		Functions\when( 'get_comments' )->justReturn(
			array(
				$this->comment( 10, 'Accepted author' ),
				$this->comment( 11, 'Rejected author' ),
			)
		);
		Functions\when( 'get_comment_meta' )->alias(
			static function ( int $comment_id, string $meta_key ): int|string {
				if ( BrokerReviewMeta::RATING === $meta_key ) {
					return 5;
				}

				return 11 === $comment_id ? BrokerReviewMeta::REJECTED : BrokerReviewMeta::MANUALLY_VERIFIED;
			}
		);
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'wp_strip_all_tags' )->alias(
			static fn( string $text ): string => preg_replace( '/<[^>]*>/', '', $text ) ?? ''
		);

		$reviews = ( new BrokerReviewRepository( new WpdbDouble() ) )->latest( 50 );

		self::assertCount( 1, $reviews );
		self::assertSame( 'Accepted author', $reviews[0]['author'] );
		self::assertFalse( $reviews[0]['verified'] );
		self::assertArrayNotHasKey( 'email', $reviews[0] );
	}

	private function comment( int $comment_id, string $author ): \WP_Comment {
		return new \WP_Comment(
			(object) array(
				'comment_ID'       => $comment_id,
				'comment_author'   => $author,
				'comment_content'  => '<p>Useful service</p>',
				'comment_date_gmt' => '2026-09-16 10:00:00',
			)
		);
	}
}

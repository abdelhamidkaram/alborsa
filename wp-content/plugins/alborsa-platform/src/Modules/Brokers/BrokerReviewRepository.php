<?php
/**
 * Reads approved broker reviews without exposing reviewer contact data.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\Brokers;

use WP_Error;

defined( 'ABSPATH' ) || exit;

final class BrokerReviewRepository {
	private const PUBLIC_REVIEW_LIMIT = 20;

	public function __construct( private \wpdb $wpdb ) {
	}

	/** @return array{count: int, average: string|null, verified_count: int, verification_policy: string}|WP_Error */
	public function summary( int $broker_id ): array|WP_Error {
		$query = $this->wpdb->prepare(
			'SELECT COUNT(*) AS review_count, ROUND(AVG(CAST(cm.meta_value AS DECIMAL(3,2))), 2) AS average_rating FROM %i c INNER JOIN %i cm ON cm.comment_id = c.comment_ID LEFT JOIN %i vm ON vm.comment_id = c.comment_ID AND vm.meta_key = %s WHERE c.comment_post_ID = %d AND c.comment_type = %s AND c.comment_approved = %s AND cm.meta_key = %s AND cm.meta_value IN (%s, %s, %s, %s, %s) AND (vm.meta_value IS NULL OR vm.meta_value <> %s)',
			$this->wpdb->prefix . 'comments',
			$this->wpdb->prefix . 'commentmeta',
			$this->wpdb->prefix . 'commentmeta',
			BrokerReviewMeta::VERIFICATION_STATUS,
			$broker_id,
			BrokerReviewMeta::COMMENT_TYPE,
			'1',
			BrokerReviewMeta::RATING,
			'1',
			'2',
			'3',
			'4',
			'5',
			BrokerReviewMeta::REJECTED
		);
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The preceding wpdb::prepare() binds both identifiers and every value.
		$rows = $this->wpdb->get_results( $query, defined( 'ARRAY_A' ) ? ARRAY_A : 'ARRAY_A' );

		if ( null === $rows && '' !== $this->wpdb->last_error ) {
			return $this->database_error();
		}

		$row = is_array( $rows ) && isset( $rows[0] ) && is_array( $rows[0] ) ? $rows[0] : array();

		return array(
			'count'               => max( 0, (int) ( $row['review_count'] ?? 0 ) ),
			'average'             => $this->average( $row['average_rating'] ?? null ),
			'verified_count'      => 0,
			'verification_policy' => 'pending',
		);
	}

	/** @return list<array<string, int|string|bool|null>> */
	public function latest( int $broker_id ): array {
		$comments = get_comments(
			array(
				'post_id' => $broker_id,
				'type'    => BrokerReviewMeta::COMMENT_TYPE,
				'status'  => 'approve',
				'number'  => self::PUBLIC_REVIEW_LIMIT,
				'orderby' => 'comment_date_gmt',
				'order'   => 'DESC',
			)
		);
		$comments = is_array( $comments ) ? $comments : array();
		$reviews  = array();

		foreach ( $comments as $comment ) {
			if ( ! $comment instanceof \WP_Comment ) {
				continue;
			}

			$comment_id = (int) $comment->comment_ID;
			$rating     = (int) get_comment_meta( $comment_id, BrokerReviewMeta::RATING, true );
			$status     = (string) get_comment_meta( $comment_id, BrokerReviewMeta::VERIFICATION_STATUS, true );

			if ( $rating < 1 || $rating > 5 || BrokerReviewMeta::REJECTED === $status ) {
				continue;
			}

			$reviews[] = array(
				'id'         => $comment_id,
				'author'     => sanitize_text_field( $comment->comment_author ),
				'content'    => wp_strip_all_tags( $comment->comment_content ),
				'rating'     => $rating,
				'created_at' => $this->iso_timestamp( $comment->comment_date_gmt ),
				'verified'   => false,
			);
		}

		return $reviews;
	}

	private function average( mixed $average ): ?string {
		if ( ! is_scalar( $average ) || ! preg_match( '/\A[1-5](?:\.\d{1,2})?\z/', (string) $average ) ) {
			return null;
		}

		return (string) $average;
	}

	private function iso_timestamp( string $timestamp ): ?string {
		return preg_match( '/\A\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\z/', $timestamp )
			? str_replace( ' ', 'T', $timestamp ) . 'Z'
			: null;
	}

	private function database_error(): WP_Error {
		return new WP_Error(
			'alborsa_platform_broker_reviews_unavailable',
			__( 'Broker review statistics are temporarily unavailable.', 'alborsa-platform' ),
			array( 'status' => 503 )
		);
	}
}

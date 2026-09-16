<?php
/**
 * WordPress privacy exporter and eraser for broker review metadata.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\Brokers;

defined( 'ABSPATH' ) || exit;

final class BrokerReviewPrivacy {
	private const PAGE_SIZE = 50;
	private const KEY       = 'alborsa-platform-broker-reviews';
	private const META_KEYS = array(
		BrokerReviewMeta::RATING,
		BrokerReviewMeta::VERIFICATION_STATUS,
		BrokerReviewMeta::EXPERIENCE_TYPE,
		BrokerReviewMeta::EXPERIENCE_DATE,
		BrokerReviewMeta::MODERATION_NOTE,
		BrokerReviewMeta::CONSENT_VERSION,
	);

	public function register(): void {
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_eraser' ) );
	}

	/**
	 * @param array<string, array<string, mixed>> $exporters Registered exporters.
	 * @return array<string, array<string, mixed>>
	 */
	public function register_exporter( array $exporters ): array {
		return array(
			self::KEY => array(
				'exporter_friendly_name' => __( 'Alborsa broker review metadata', 'alborsa-platform' ),
				'callback'               => array( $this, 'export' ),
			),
		) + $exporters;
	}

	/**
	 * @param array<string, array<string, mixed>> $erasers Registered erasers.
	 * @return array<string, array<string, mixed>>
	 */
	public function register_eraser( array $erasers ): array {
		return array(
			self::KEY => array(
				'eraser_friendly_name' => __( 'Alborsa broker review metadata', 'alborsa-platform' ),
				'callback'             => array( $this, 'erase' ),
			),
		) + $erasers;
	}

	/**
	 * @return array{data: list<array<string, mixed>>, done: bool}
	 */
	public function export( string $email_address, int $page = 1 ): array {
		$comments = $this->comments_for_email( $email_address, $page );
		$data     = array();

		foreach ( $comments as $comment ) {
			$data[] = array(
				'group_id'    => self::KEY,
				'group_label' => __( 'Broker reviews', 'alborsa-platform' ),
				'item_id'     => 'comment-' . $comment->comment_ID,
				'data'        => $this->export_fields( (int) $comment->comment_ID ),
			);
		}

		return array(
			'data' => $data,
			'done' => count( $comments ) < self::PAGE_SIZE,
		);
	}

	/**
	 * @return array{items_removed: bool, items_retained: bool, messages: list<string>, done: bool}
	 */
	public function erase( string $email_address, int $page = 1 ): array {
		$comments      = $this->comments_for_email( $email_address, $page );
		$items_removed = false;

		foreach ( $comments as $comment ) {
			foreach ( self::META_KEYS as $meta_key ) {
				$items_removed = delete_comment_meta( (int) $comment->comment_ID, $meta_key ) || $items_removed;
			}
		}

		return array(
			'items_removed'  => $items_removed,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => count( $comments ) < self::PAGE_SIZE,
		);
	}

	/** @return list<\WP_Comment> */
	private function comments_for_email( string $email_address, int $page ): array {
		$email_address = sanitize_email( $email_address );

		if ( '' === $email_address ) {
			return array();
		}

		$comments = get_comments(
			array(
				'author_email' => $email_address,
				'type'         => BrokerReviewMeta::COMMENT_TYPE,
				'status'       => 'all',
				'number'       => self::PAGE_SIZE,
				'offset'       => max( 0, $page - 1 ) * self::PAGE_SIZE,
				'orderby'      => 'comment_ID',
				'order'        => 'ASC',
			)
		);

		if ( ! is_array( $comments ) ) {
			return array();
		}

		return array_values( array_filter( $comments, static fn ( mixed $comment ): bool => $comment instanceof \WP_Comment ) );
	}

	/** @return list<array{name: string, value: string}> */
	private function export_fields( int $comment_id ): array {
		$labels = array(
			BrokerReviewMeta::RATING              => __( 'Rating', 'alborsa-platform' ),
			BrokerReviewMeta::VERIFICATION_STATUS => __( 'Verification status', 'alborsa-platform' ),
			BrokerReviewMeta::EXPERIENCE_TYPE     => __( 'Experience type', 'alborsa-platform' ),
			BrokerReviewMeta::EXPERIENCE_DATE     => __( 'Experience date', 'alborsa-platform' ),
			BrokerReviewMeta::MODERATION_NOTE     => __( 'Moderation note', 'alborsa-platform' ),
			BrokerReviewMeta::CONSENT_VERSION     => __( 'Consent version', 'alborsa-platform' ),
		);
		$fields = array();

		foreach ( $labels as $meta_key => $label ) {
			$value = get_comment_meta( $comment_id, $meta_key, true );

			if ( ! is_scalar( $value ) || '' === (string) $value ) {
				continue;
			}

			$fields[] = array(
				'name'  => $label,
				'value' => (string) $value,
			);
		}

		return $fields;
	}
}

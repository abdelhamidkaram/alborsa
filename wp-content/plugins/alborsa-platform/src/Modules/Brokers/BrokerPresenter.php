<?php
/**
 * Public broker REST representations.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\Brokers;

use WP_Error;

defined( 'ABSPATH' ) || exit;

final class BrokerPresenter {
	public function __construct(
		private BrokerMeta $meta,
		private BrokerReviewRepository $reviews
	) {
	}

	/** @return array<string, mixed>|WP_Error */
	public function summary( \WP_Post $broker ): array|WP_Error {
		$broker_types  = $this->term_slugs( $broker->ID, BrokersModule::TYPE_TAXONOMY );
		$jurisdictions = $this->term_slugs( $broker->ID, BrokersModule::JURISDICTION_TAXONOMY );

		if ( $broker_types instanceof WP_Error || $jurisdictions instanceof WP_Error ) {
			return $this->classification_error();
		}

		return array(
			'id'               => $broker->ID,
			'slug'             => $broker->post_name,
			'name'             => wp_strip_all_tags( (string) get_the_title( $broker ) ),
			'excerpt'          => wp_strip_all_tags( (string) get_the_excerpt( $broker ) ),
			'url'              => esc_url_raw( get_permalink( $broker ) ),
			'legal_name'       => $this->text_meta( $broker, BrokerMeta::LEGAL_NAME ),
			'official_website' => esc_url_raw( $this->text_meta( $broker, BrokerMeta::OFFICIAL_WEBSITE ) ),
			'broker_types'     => $broker_types,
			'jurisdictions'    => $jurisdictions,
			'services'         => $this->list_meta( $broker, BrokerMeta::SERVICES ),
			'platforms'        => $this->list_meta( $broker, BrokerMeta::PLATFORMS ),
			'arabic_support'   => (bool) get_post_meta( $broker->ID, BrokerMeta::ARABIC_SUPPORT, true ),
			'minimum_deposit'  => $this->text_meta( $broker, BrokerMeta::MINIMUM_DEPOSIT ),
			'deposit_currency' => $this->text_meta( $broker, BrokerMeta::DEPOSIT_CURRENCY ),
			'editorial_rating' => $this->pending_editorial_rating(),
			'affiliate_offer'  => array( 'available' => false ),
		);
	}

	/** @return array<string, mixed>|WP_Error */
	public function detail( \WP_Post $broker ): array|WP_Error {
		$broker_data = $this->comparison( $broker );

		if ( $broker_data instanceof WP_Error ) {
			return $broker_data;
		}

		$broker_data['content'] = wp_kses_post( $broker->post_content );
		$broker_data['reviews'] = $this->reviews->latest( $broker->ID );

		return $broker_data;
	}

	/** @return array<string, mixed>|WP_Error */
	public function comparison( \WP_Post $broker ): array|WP_Error {
		$broker_data = $this->summary( $broker );

		if ( $broker_data instanceof WP_Error ) {
			return $broker_data;
		}

		$review_summary = $this->reviews->summary( $broker->ID );

		if ( $review_summary instanceof WP_Error ) {
			return $review_summary;
		}

		return array_merge(
			$broker_data,
			array(
				'currencies'      => $this->list_meta( $broker, BrokerMeta::CURRENCIES ),
				'deposit_methods' => $this->list_meta( $broker, BrokerMeta::DEPOSIT_METHODS ),
				'account_types'   => $this->list_meta( $broker, BrokerMeta::ACCOUNT_TYPES ),
				'fee_summary'     => $this->text_meta( $broker, BrokerMeta::FEE_SUMMARY ),
				'pros'            => $this->list_meta( $broker, BrokerMeta::PROS ),
				'cons'            => $this->list_meta( $broker, BrokerMeta::CONS ),
				'licenses'        => $this->public_licenses( $broker ),
				'user_rating'     => $review_summary,
			)
		);
	}

	/** @return list<string>|WP_Error */
	private function term_slugs( int $broker_id, string $taxonomy ): array|WP_Error {
		$terms = get_the_terms( $broker_id, $taxonomy );

		if ( $terms instanceof WP_Error ) {
			return $terms;
		}

		if ( false === $terms ) {
			return array();
		}

		return array_values( array_map( static fn( \WP_Term $term ): string => $term->slug, $terms ) );
	}

	private function text_meta( \WP_Post $broker, string $meta_key ): string {
		$value = get_post_meta( $broker->ID, $meta_key, true );

		return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
	}

	/** @return list<string> */
	private function list_meta( \WP_Post $broker, string $meta_key ): array {
		return $this->meta->sanitize_list( get_post_meta( $broker->ID, $meta_key, true ) );
	}

	/** @return list<array<string, string>> */
	private function public_licenses( \WP_Post $broker ): array {
		$licenses = $this->meta->sanitize_licenses( get_post_meta( $broker->ID, BrokerMeta::LICENSES, true ) );

		return array_values(
			array_map(
				static fn( array $license ): array => array(
					'regulator'      => $license['regulator'],
					'country'        => $license['country'],
					'legal_entity'   => $license['legal_entity'],
					'license_number' => $license['license_number'],
					'source_url'     => $license['source_url'],
					'verified_at'    => $license['verified_at'],
				),
				array_filter(
					$licenses,
					static fn( array $license ): bool => BrokerMeta::LICENSE_VERIFIED === $license['verification_status']
						&& '' !== $license['regulator']
						&& '' !== $license['license_number']
						&& '' !== $license['source_url']
						&& '' !== $license['verified_at']
				)
			)
		);
	}

	/** @return array{status: string, score: null, methodology: null} */
	private function pending_editorial_rating(): array {
		return array(
			'status'      => 'methodology_pending',
			'score'       => null,
			'methodology' => null,
		);
	}

	private function classification_error(): WP_Error {
		return new WP_Error(
			'alborsa_platform_broker_classifications_unavailable',
			__( 'Broker classifications are temporarily unavailable.', 'alborsa-platform' ),
			array( 'status' => 503 )
		);
	}
}

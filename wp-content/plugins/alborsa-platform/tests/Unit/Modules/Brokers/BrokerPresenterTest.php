<?php
/**
 * Broker presenter tests.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Tests\Unit\Modules\Brokers;

use Alborsa\Platform\Modules\Brokers\BrokerMeta;
use Alborsa\Platform\Modules\Brokers\BrokerPresenter;
use Alborsa\Platform\Modules\Brokers\BrokerReviewRepository;
use Alborsa\Platform\Modules\Brokers\BrokersModule;
use Alborsa\Platform\Tests\Support\WpdbDouble;
use Alborsa\Platform\Tests\UnitTestCase;
use Brain\Monkey\Functions;

final class BrokerPresenterTest extends UnitTestCase {
	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'sanitize_text_field' )->alias( static fn( string $text ): string => trim( $text ) );
		Functions\when( 'sanitize_textarea_field' )->alias( static fn( string $text ): string => trim( $text ) );
		Functions\when( 'sanitize_key' )->returnArg();
		Functions\when( 'wp_strip_all_tags' )->alias(
			static fn( string $text ): string => preg_replace( '/<[^>]*>/', '', $text ) ?? ''
		);
		Functions\when( 'get_the_title' )->justReturn( 'Example Broker' );
		Functions\when( 'get_the_excerpt' )->justReturn( 'Broker summary' );
		Functions\when( 'get_permalink' )->justReturn( 'https://example.test/brokers/example/' );
		Functions\when( 'esc_url_raw' )->alias(
			static fn( string $url ): string => str_starts_with( $url, 'https://' ) ? $url : ''
		);
		Functions\when( 'get_the_terms' )->alias(
			static fn( int $post_id, string $taxonomy ): array => array(
				new \WP_Term( (object) array( 'slug' => BrokersModule::TYPE_TAXONOMY === $taxonomy ? 'forex' : 'ksa' ) ),
			),
		);
	}

	public function test_comparison_exposes_only_complete_verified_licenses_and_separate_rating_states(): void {
		Functions\when( 'get_post_meta' )->alias(
			fn( int $post_id, string $meta_key ): mixed => $this->meta_value( $meta_key )
		);
		$wpdb      = new class() extends WpdbDouble {
			public function get_results( string $query, string|int $output = 'OBJECT' ): ?array {
				parent::get_results( $query, $output );

				return array(
					array(
						'review_count'   => '3',
						'average_rating' => '4.67',
					),
				);
			}
		};
		$presenter = new BrokerPresenter( new BrokerMeta(), new BrokerReviewRepository( $wpdb ) );

		$comparison = $presenter->comparison( $this->broker() );

		self::assertFalse( is_wp_error( $comparison ) );
		self::assertCount( 1, $comparison['licenses'] );
		self::assertSame( 'FCA', $comparison['licenses'][0]['regulator'] );
		self::assertArrayNotHasKey( 'internal_notes', $comparison['licenses'][0] );
		self::assertSame( 'methodology_pending', $comparison['editorial_rating']['status'] );
		self::assertNull( $comparison['editorial_rating']['score'] );
		self::assertSame( '4.67', $comparison['user_rating']['average'] );
		self::assertSame( 0, $comparison['user_rating']['verified_count'] );
		self::assertFalse( $comparison['affiliate_offer']['available'] );
	}

	private function broker(): \WP_Post {
		return new \WP_Post(
			(object) array(
				'ID'           => 50,
				'post_type'    => BrokersModule::POST_TYPE,
				'post_name'    => 'example',
				'post_status'  => 'publish',
				'post_content' => 'Broker body',
			)
		);
	}

	private function meta_value( string $meta_key ): mixed {
		if ( BrokerMeta::LICENSES === $meta_key ) {
			return array(
				$this->license(),
				$this->license( array( 'source_url' => '' ) ),
				$this->license( array( 'verification_status' => BrokerMeta::LICENSE_UNVERIFIED ) ),
			);
		}

		return match ( $meta_key ) {
			BrokerMeta::LEGAL_NAME       => 'Example Markets Ltd',
			BrokerMeta::OFFICIAL_WEBSITE => 'https://example.test',
			BrokerMeta::ARABIC_SUPPORT   => '1',
			BrokerMeta::PLATFORMS        => array( 'Web', 'Mobile' ),
			default                      => array(),
		};
	}

	/**
	 * @param array<string, string> $overrides License overrides.
	 * @return array<string, string>
	 */
	private function license( array $overrides = array() ): array {
		return array_merge(
			array(
				'regulator'           => 'FCA',
				'country'             => 'UK',
				'legal_entity'        => 'Example Markets Ltd',
				'license_number'      => '12345',
				'verification_status' => BrokerMeta::LICENSE_VERIFIED,
				'source_url'          => 'https://example.test/register/12345',
				'verified_at'         => '2026-09-16',
				'internal_notes'      => 'Never expose',
			),
			$overrides
		);
	}
}

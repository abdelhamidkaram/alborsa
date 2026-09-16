<?php
/**
 * Validated content-to-asset relationship operations.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Shared\Relationships;

use Alborsa\Platform\Modules\Assets\AssetsModule;
use Alborsa\Platform\Modules\Editorial\EditorialModule;
use WP_Error;

defined( 'ABSPATH' ) || exit;

final class ContentAssetRelationshipService {
	public const MENTIONS = 'mentions';
	public const ANALYZES = 'analyzes';

	public const CONTENT_TYPES = array(
		'post',
		EditorialModule::ANALYSIS_POST_TYPE,
		EditorialModule::GUIDE_POST_TYPE,
	);

	private const RELATION_TYPES = array(
		self::MENTIONS,
		self::ANALYZES,
	);

	private RelationshipRepository $repository;

	public function __construct( RelationshipRepository $repository ) {
		$this->repository = $repository;
	}

	public function link( \WP_Post $content, \WP_Post $asset, string $relation_type, int $sort_order ): int|WP_Error {
		$validation_error = $this->validation_error( $content, $asset, $relation_type );

		if ( $validation_error instanceof WP_Error ) {
			return $validation_error;
		}

		return $this->repository->add( $this->relationship( $content, $asset, $relation_type, $sort_order ) );
	}

	public function unlink( \WP_Post $content, \WP_Post $asset, string $relation_type ): bool|WP_Error {
		$validation_error = $this->validation_error( $content, $asset, $relation_type );

		if ( $validation_error instanceof WP_Error ) {
			return $validation_error;
		}

		return $this->repository->delete( $this->relationship( $content, $asset, $relation_type, 0 ) );
	}

	/**
	 * @return list<array<string, mixed>>|WP_Error
	 */
	public function for_content( \WP_Post $content ): array|WP_Error {
		if ( ! $this->supports_content( $content ) ) {
			return $this->invalid_content_error();
		}

		return $this->repository->find_by_source( $content->ID, $content->post_type );
	}

	/**
	 * @return list<array<string, mixed>>|WP_Error
	 */
	public function for_asset( \WP_Post $asset ): array|WP_Error {
		if ( ! $this->is_asset( $asset ) ) {
			return $this->invalid_asset_error();
		}

		return $this->repository->find_by_target( $asset->ID, AssetsModule::POST_TYPE );
	}

	public function supports_content( \WP_Post $content ): bool {
		return $content->ID > 0 && in_array( $content->post_type, self::CONTENT_TYPES, true );
	}

	private function validation_error(
		\WP_Post $content,
		\WP_Post $asset,
		string $relation_type
	): ?WP_Error {
		if ( ! $this->supports_content( $content ) ) {
			return $this->invalid_content_error();
		}

		if ( ! $this->is_asset( $asset ) ) {
			return $this->invalid_asset_error();
		}

		if ( ! in_array( $relation_type, self::RELATION_TYPES, true ) ) {
			return new WP_Error(
				'alborsa_platform_relationship_type_not_allowed',
				__( 'This content-to-asset relationship type is not allowed.', 'alborsa-platform' )
			);
		}

		return null;
	}

	private function is_asset( \WP_Post $asset ): bool {
		return $asset->ID > 0 && AssetsModule::POST_TYPE === $asset->post_type;
	}

	private function invalid_content_error(): WP_Error {
		return new WP_Error(
			'alborsa_platform_relationship_content_not_allowed',
			__( 'The relationship source must be supported Alborsa content.', 'alborsa-platform' )
		);
	}

	private function invalid_asset_error(): WP_Error {
		return new WP_Error(
			'alborsa_platform_relationship_asset_required',
			__( 'The relationship target must be an Alborsa asset.', 'alborsa-platform' )
		);
	}

	/**
	 * @return array<string, int|string>
	 */
	private function relationship(
		\WP_Post $content,
		\WP_Post $asset,
		string $relation_type,
		int $sort_order
	): array {
		return array(
			'from_object_id'   => $content->ID,
			'from_object_type' => $content->post_type,
			'to_object_id'     => $asset->ID,
			'to_object_type'   => AssetsModule::POST_TYPE,
			'relation_type'    => $relation_type,
			'sort_order'       => $sort_order,
		);
	}
}

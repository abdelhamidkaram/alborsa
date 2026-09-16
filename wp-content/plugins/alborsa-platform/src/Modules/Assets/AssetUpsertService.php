<?php
/**
 * Validated asset create/update service shared by import and future admin tools.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

namespace Alborsa\Platform\Modules\Assets;

use WP_Error;

defined( 'ABSPATH' ) || exit;

final class AssetUpsertService {
	private const ALLOWED_STATUSES = array( 'draft', 'pending', 'publish', 'private' );

	private AssetMeta $meta;
	private AssetLanguageGateway $languages;

	public function __construct( AssetMeta $meta, AssetLanguageGateway $languages ) {
		$this->meta      = $meta;
		$this->languages = $languages;
	}

	/**
	 * @param array<string, string> $row CSV asset values.
	 * @return array{status: string, post_id: int}|WP_Error
	 */
	public function upsert( array $row, bool $dry_run ): array|WP_Error {
		$asset = $this->prepare_asset( $row );

		if ( $asset instanceof WP_Error ) {
			return $asset;
		}

		$existing_id = $this->existing_asset_id( $asset['language'], $asset['meta'][ AssetMeta::CANONICAL_SYMBOL ] );

		if ( $existing_id instanceof WP_Error ) {
			return $existing_id;
		}

		if ( $dry_run ) {
			return array(
				'status'  => 0 === $existing_id ? 'would_create' : 'would_update',
				'post_id' => $existing_id,
			);
		}

		return $this->save( $asset, $existing_id );
	}

	/**
	 * @param array<string, string> $row CSV asset values.
	 * @return string|WP_Error
	 */
	public function identity_key( array $row ): string|WP_Error {
		$language = $this->language( $row['language'] ?? '' );

		if ( $language instanceof WP_Error ) {
			return $language;
		}

		$symbol = $this->meta->sanitize_symbol( $row['canonical_symbol'] ?? '' );

		if ( '' === $symbol ) {
			return $this->row_error( 'invalid_symbol', __( 'canonical_symbol is required and must contain a valid asset symbol.', 'alborsa-platform' ) );
		}

		return $language . "\0" . $symbol;
	}

	/**
	 * @param array<string, string> $row CSV asset values.
	 * @return array{
	 *   language: string,
	 *   title: string,
	 *   slug: string|null,
	 *   post_status: string|null,
	 *   excerpt: string|null,
	 *   content: string|null,
	 *   meta: array<string, string>,
	 *   terms: array<string, list<int>>
	 * }|WP_Error
	 */
	private function prepare_asset( array $row ): array|WP_Error {
		$language = $this->language( $row['language'] ?? '' );

		if ( $language instanceof WP_Error ) {
			return $language;
		}

		$symbol = $this->meta->sanitize_symbol( $row['canonical_symbol'] ?? '' );

		if ( '' === $symbol ) {
			return $this->row_error( 'invalid_symbol', __( 'canonical_symbol is required and must contain a valid asset symbol.', 'alborsa-platform' ) );
		}

		$title = sanitize_text_field( $row['title'] ?? '' );

		if ( '' === $title ) {
			return $this->row_error( 'missing_title', __( 'title is required.', 'alborsa-platform' ) );
		}

		$status = $this->post_status( $row );

		if ( $status instanceof WP_Error ) {
			return $status;
		}

		if ( 'en' === $language && in_array( $status, array( 'publish', 'private' ), true ) ) {
			return $this->row_error( 'english_not_publishable', __( 'English assets must remain unpublished during the current phase.', 'alborsa-platform' ) );
		}

		$meta = $this->metadata( $row, $symbol );

		if ( $meta instanceof WP_Error ) {
			return $meta;
		}

		$terms = $this->terms( $row, $language );

		if ( $terms instanceof WP_Error ) {
			return $terms;
		}

		$slug = isset( $row['slug'] ) && '' !== $row['slug'] ? sanitize_title( $row['slug'] ) : null;

		if ( isset( $row['slug'] ) && '' !== $row['slug'] && '' === $slug ) {
			return $this->row_error( 'invalid_slug', __( 'slug could not be generated from the supplied value.', 'alborsa-platform' ) );
		}

		return array(
			'language'    => $language,
			'title'       => $title,
			'slug'        => $slug,
			'post_status' => $status,
			'excerpt'     => array_key_exists( 'excerpt', $row ) ? wp_kses_post( $row['excerpt'] ) : null,
			'content'     => array_key_exists( 'content', $row ) ? wp_kses_post( $row['content'] ) : null,
			'meta'        => $meta,
			'terms'       => $terms,
		);
	}

	/**
	 * @param array<string, string> $row CSV asset values.
	 * @return array<string, string>|WP_Error
	 */
	private function metadata( array $row, string $symbol ): array|WP_Error {
		$metadata = array( AssetMeta::CANONICAL_SYMBOL => $symbol );
		$fields   = array(
			AssetMeta::BASE_CURRENCY    => array( 'base_currency', array( $this->meta, 'sanitize_currency' ) ),
			AssetMeta::QUOTE_CURRENCY   => array( 'quote_currency', array( $this->meta, 'sanitize_currency' ) ),
			AssetMeta::ISIN             => array( 'isin', array( $this->meta, 'sanitize_isin' ) ),
			AssetMeta::OFFICIAL_WEBSITE => array( 'official_website', array( $this->meta, 'sanitize_http_url' ) ),
			AssetMeta::LISTING_STATUS   => array( 'listing_status', array( $this->meta, 'sanitize_listing_status' ) ),
		);

		foreach ( $fields as $meta_key => $definition ) {
			list( $column, $sanitizer ) = $definition;

			if ( ! array_key_exists( $column, $row ) ) {
				continue;
			}

			$raw_value       = $row[ $column ];
			$sanitized_value = $sanitizer( $raw_value );

			if ( '' !== $raw_value && '' === $sanitized_value ) {
				return $this->row_error(
					'invalid_' . $column,
					sprintf(
						/* translators: %s: CSV column name. */
						__( '%s is invalid.', 'alborsa-platform' ),
						$column
					)
				);
			}

			$metadata[ $meta_key ] = $sanitized_value;
		}

		return $metadata;
	}

	/**
	 * @param array<string, string> $row CSV asset values.
	 * @return array<string, list<int>>|WP_Error
	 */
	private function terms( array $row, string $language ): array|WP_Error {
		$columns = array(
			AssetsModule::TYPE_TAXONOMY     => 'asset_type',
			AssetsModule::EXCHANGE_TAXONOMY => 'exchange',
			AssetsModule::SECTOR_TAXONOMY   => 'sector',
		);
		$terms   = array();

		foreach ( $columns as $taxonomy => $column ) {
			if ( AssetsModule::TYPE_TAXONOMY !== $taxonomy && ! array_key_exists( $column, $row ) ) {
				continue;
			}

			$slug = sanitize_title( $row[ $column ] ?? '' );

			if ( AssetsModule::TYPE_TAXONOMY === $taxonomy && '' === $slug ) {
				return $this->row_error( 'missing_asset_type', __( 'asset_type is required.', 'alborsa-platform' ) );
			}

			if ( '' === $slug ) {
				$terms[ $taxonomy ] = array();
				continue;
			}

			$term_id = $this->term_id( $slug, $taxonomy, $language );

			if ( $term_id instanceof WP_Error ) {
				return $term_id;
			}

			$terms[ $taxonomy ] = array( $term_id );
		}

		return $terms;
	}

	/**
	 * @param array<string, string> $row CSV asset values.
	 */
	private function post_status( array $row ): string|WP_Error|null {
		if ( ! isset( $row['post_status'] ) || '' === $row['post_status'] ) {
			return null;
		}

		$status = sanitize_key( $row['post_status'] );

		if ( ! in_array( $status, self::ALLOWED_STATUSES, true ) ) {
			return $this->row_error( 'invalid_status', __( 'post_status must be draft, pending, publish, or private.', 'alborsa-platform' ) );
		}

		return $status;
	}

	private function language( string $language ): string|WP_Error {
		$language = sanitize_key( $language );

		if ( '' === $language ) {
			return $this->row_error( 'missing_language', __( 'language is required.', 'alborsa-platform' ) );
		}

		if ( ! $this->languages->available() ) {
			return $this->row_error( 'polylang_unavailable', __( 'Polylang language functions are unavailable.', 'alborsa-platform' ) );
		}

		if ( ! $this->languages->has_language( $language ) ) {
			return $this->row_error( 'unknown_language', __( 'language is not configured in Polylang.', 'alborsa-platform' ) );
		}

		return $language;
	}

	private function term_id( string $slug, string $taxonomy, string $language ): int|WP_Error {
		$term = term_exists( $slug, $taxonomy );

		if ( null === $term ) {
			return $this->row_error(
				'unknown_term',
				sprintf(
					/* translators: 1: taxonomy name, 2: term slug. */
					__( '%1$s term "%2$s" does not exist.', 'alborsa-platform' ),
					$taxonomy,
					$slug
				)
			);
		}

		$term_id       = is_array( $term ) ? (int) $term['term_id'] : (int) $term;
		$term_language = $this->languages->term_language( $term_id );

		if ( $language !== $term_language ) {
			return $this->row_error(
				'term_language_mismatch',
				sprintf(
					/* translators: 1: taxonomy name, 2: term slug, 3: language slug. */
					__( '%1$s term "%2$s" is not assigned to language "%3$s".', 'alborsa-platform' ),
					$taxonomy,
					$slug,
					$language
				)
			);
		}

		return $term_id;
	}

	private function existing_asset_id( string $language, string $symbol ): int|WP_Error {
		$matches = get_posts(
			array(
				'post_type'              => AssetsModule::POST_TYPE,
				'post_status'            => array( 'publish', 'pending', 'draft', 'future', 'private', 'trash' ),
				'posts_per_page'         => 2,
				'fields'                 => 'ids',
				'meta_key'               => AssetMeta::CANONICAL_SYMBOL,
				'meta_value'             => $symbol,
				'lang'                   => $language,
				'no_found_rows'          => true,
				'suppress_filters'       => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		if ( count( $matches ) > 1 ) {
			return $this->row_error( 'duplicate_existing_asset', __( 'Multiple assets already use this language and canonical_symbol.', 'alborsa-platform' ) );
		}

		return isset( $matches[0] ) ? (int) $matches[0] : 0;
	}

	/**
	 * @param array{
	 *   language: string,
	 *   title: string,
	 *   slug: string|null,
	 *   post_status: string|null,
	 *   excerpt: string|null,
	 *   content: string|null,
	 *   meta: array<string, string>,
	 *   terms: array<string, list<int>>
	 * } $asset Prepared asset values.
	 * @return array{status: string, post_id: int}|WP_Error
	 */
	private function save( array $asset, int $existing_id ): array|WP_Error {
		$post_data = array(
			'post_type'  => AssetsModule::POST_TYPE,
			'post_title' => wp_slash( $asset['title'] ),
		);

		if ( 0 !== $existing_id ) {
			$post_data['ID'] = $existing_id;
		} else {
			$post_data['post_name']   = $asset['slug'] ?? sanitize_title( $asset['meta'][ AssetMeta::CANONICAL_SYMBOL ] );
			$post_data['post_status'] = $asset['post_status'] ?? 'draft';
		}

		if ( null !== $asset['slug'] ) {
			$post_data['post_name'] = $asset['slug'];
		}

		if ( null !== $asset['post_status'] ) {
			$post_data['post_status'] = $asset['post_status'];
		}

		if ( null !== $asset['excerpt'] ) {
			$post_data['post_excerpt'] = wp_slash( $asset['excerpt'] );
		}

		if ( null !== $asset['content'] ) {
			$post_data['post_content'] = wp_slash( $asset['content'] );
		}

		$post_id = wp_insert_post( $post_data, true );

		if ( $post_id instanceof WP_Error ) {
			return $post_id;
		}

		$this->languages->set_post_language( $post_id, $asset['language'] );

		foreach ( $asset['meta'] as $meta_key => $meta_value ) {
			update_post_meta( $post_id, $meta_key, $meta_value );
		}

		foreach ( $asset['terms'] as $taxonomy => $term_ids ) {
			$term_result = wp_set_object_terms( $post_id, $term_ids, $taxonomy, false );

			if ( $term_result instanceof WP_Error ) {
				return $term_result;
			}
		}

		return array(
			'status'  => 0 === $existing_id ? 'created' : 'updated',
			'post_id' => $post_id,
		);
	}

	private function row_error( string $suffix, string $message ): WP_Error {
		return new WP_Error( 'alborsa_platform_asset_import_' . $suffix, $message );
	}
}

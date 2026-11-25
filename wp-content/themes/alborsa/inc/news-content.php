<?php
/**
 * Bounded read models for the news archive and article templates.
 *
 * @package Alborsa
 */

declare(strict_types=1);

namespace Alborsa\Theme\NewsContent;

use function Alborsa\Theme\Content\published_assets;
use function Alborsa\Theme\TemplateTags\news_url;

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the selected archive content type.
 */
function selected_type(): string {
	$type = request_key( 'news_type' );

	return in_array( $type, array( 'news', 'analysis' ), true ) ? $type : 'all';
}

/**
 * Mark the first investor section in rendered content without changing the saved article.
 */
function mark_investor_heading( string $content ): string {
	$html = new \WP_HTML_Tag_Processor( $content );

	while ( $html->next_tag() ) {
		$tag = $html->get_tag();
		if ( 'H2' !== $tag && 'H3' !== $tag ) {
			continue;
		}

		$html->set_bookmark( 'heading' );
		if ( is_investor_heading( $html, $tag ) && $html->seek( 'heading' ) ) {
			$html->add_class( 'news-investor-heading' );
			return $html->get_updated_html();
		}
	}

	return $content;
}

/**
 * Read heading text across inline formatting and HTML entities.
 */
function is_investor_heading( \WP_HTML_Tag_Processor $html, string $tag ): bool {
	$heading_text = '';
	while ( $html->next_token() ) {
		if ( $tag === $html->get_tag() && $html->is_tag_closer() ) {
			$heading_text = preg_replace( '/[\s\p{Z}]+/u', ' ', $heading_text ) ?? '';
			return 1 === preg_match( '/\Aماذا يعني هذا للمستثمر *[؟?]?\z/u', trim( $heading_text ) );
		}

		if ( '#text' === $html->get_token_type() ) {
			$heading_text .= $html->get_modifiable_text();
		}
	}

	return false;
}

/**
 * Return the selected topic only when it exists in the editorial taxonomy.
 */
function selected_topic(): string {
	$topic = request_topic_slug();

	if ( '' === $topic || ! taxonomy_exists( 'alb_topic' ) ) {
		return '';
	}

	return get_term_by( 'slug', $topic, 'alb_topic' ) instanceof \WP_Term ? $topic : '';
}

/**
 * Page the mixed feed independently of WordPress's posts-only main query.
 */
function archive_page_number(): int {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public GET pagination only reads published entries.
	$requested_page = isset( $_GET['news_page'] ) && is_string( $_GET['news_page'] ) ? wp_unslash( $_GET['news_page'] ) : '';
	$page_number    = filter_var( $requested_page, FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1 ) ) );

	return false === $page_number ? 1 : $page_number;
}

/**
 * Build the mixed, paginated news and analysis query.
 */
function archive_query( int $page, string $type, string $topic ): \WP_Query {
	$arguments = array(
		'post_type'              => post_types_for_filter( $type ),
		'post_status'            => 'publish',
		'posts_per_page'         => 8,
		'paged'                  => max( 1, $page ),
		'orderby'                => 'date',
		'order'                  => 'DESC',
		'ignore_sticky_posts'    => false,
		'suppress_filters'       => false,
		'update_post_meta_cache' => true,
		'update_post_term_cache' => true,
	);

	if ( '' !== $topic && taxonomy_exists( 'alb_topic' ) ) {
		$arguments['tax_query'] = array(
			array(
				'taxonomy' => 'alb_topic',
				'field'    => 'slug',
				'terms'    => array( $topic ),
			),
		);
	}

	return new \WP_Query( $arguments );
}

/**
 * Return the published totals used by the archive type tabs.
 *
 * @return array{all: int, news: int, analysis: int}
 */
function published_counts(): array {
	$news_count     = published_count( array( 'post' ) );
	$analysis_count = post_type_exists( 'alb_analysis' ) ? published_count( array( 'alb_analysis' ) ) : 0;

	return array(
		'all'      => $news_count + $analysis_count,
		'news'     => $news_count,
		'analysis' => $analysis_count,
	);
}

/**
 * Return up to eight active editorial topics.
 *
 * @return list<\WP_Term>
 */
function archive_topics(): array {
	if ( ! taxonomy_exists( 'alb_topic' ) ) {
		return array();
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'alb_topic',
			'hide_empty' => true,
			'number'     => 8,
			'orderby'    => 'count',
			'order'      => 'DESC',
		)
	);

	return is_array( $terms ) ? array_values( $terms ) : array();
}

/**
 * Build a filter URL without exposing taxonomy archive contracts.
 */
function filter_url( string $type, string $topic ): string {
	$arguments = array();

	if ( in_array( $type, array( 'news', 'analysis' ), true ) ) {
		$arguments['news_type'] = $type;
	}

	if ( '' !== $topic ) {
		$arguments['topic'] = sanitize_title( $topic );
	}

	$base_url = remove_query_arg( array( 'news_type', 'topic', 'news_page' ), news_url() );

	return array() === $arguments ? $base_url : add_query_arg( $arguments, $base_url );
}

/**
 * Return the newest published news posts for the urgent-news rail.
 *
 * @return list<\WP_Post>
 */
function urgent_posts(): array {
	$query = new \WP_Query(
		array(
			'post_type'              => 'post',
			'post_status'            => 'publish',
			'posts_per_page'         => 3,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'suppress_filters'       => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	return array_values( $query->posts );
}

/**
 * Return a bounded list of published asset identities for the sidebar.
 *
 * @return list<array{id: int, title: string, symbol: string, url: string, types: list<string>, exchanges: list<string>, type_labels: string, exchange_labels: string, quote_currency: string}>
 */
function asset_tags(): array {
	return array_slice( published_assets(), 0, 6 );
}

/**
 * Estimate article reading time from visible words.
 */
function reading_minutes( \WP_Post $post ): int {
	$text  = trim( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ) );
	$words = '' === $text ? array() : preg_split( '/\s+/u', $text );
	$count = is_array( $words ) ? count( $words ) : 0;

	return max( 1, (int) ceil( $count / 200 ) );
}

/**
 * Format the estimated reading duration with a translatable plural.
 */
function reading_label( int $minutes ): string {
	/* translators: %d: estimated reading time in minutes. */
	return sprintf( _n( 'قراءة %d دقيقة', 'قراءة %d دقائق', $minutes, 'alborsa' ), $minutes );
}

/**
 * Return related published news and analyses, preferring shared topics.
 *
 * @return list<\WP_Post>
 */
function related_posts( \WP_Post $post, int $limit = 3 ): array {
	$post_types = post_types_for_filter( 'all' );
	$term_ids   = taxonomy_exists( 'alb_topic' ) ? wp_get_object_terms( $post->ID, 'alb_topic', array( 'fields' => 'ids' ) ) : array();
	$arguments  = array(
		'post_type'              => $post_types,
		'post_status'            => 'publish',
		'post__not_in'           => array( $post->ID ),
		'posts_per_page'         => min( 6, max( 1, $limit ) ),
		'orderby'                => 'date',
		'order'                  => 'DESC',
		'no_found_rows'          => true,
		'ignore_sticky_posts'    => true,
		'suppress_filters'       => false,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => true,
	);

	if ( is_array( $term_ids ) && array() !== $term_ids ) {
		$arguments['tax_query'] = array(
			array(
				'taxonomy' => 'alb_topic',
				'field'    => 'term_id',
				'terms'    => array_map( 'absint', $term_ids ),
			),
		);
	}

	$query = new \WP_Query( $arguments );

	return array_values( $query->posts );
}

/**
 * Return a stable group key for a post date.
 */
function date_group_key( \WP_Post $post ): string {
	return get_the_date( 'Y-m-d', $post );
}

/**
 * Return the localized heading used above one date group.
 */
function date_group_label( \WP_Post $post ): string {
	$post_date = get_the_date( 'Y-m-d', $post );
	$today     = wp_date( 'Y-m-d' );
	$yesterday = ( new \DateTimeImmutable( 'now', wp_timezone() ) )->modify( '-1 day' )->format( 'Y-m-d' );

	if ( $today === $post_date ) {
		return __( 'اليوم', 'alborsa' );
	}

	if ( $yesterday === $post_date ) {
		return __( 'أمس', 'alborsa' );
	}

	return get_the_date( 'l j F', $post );
}

/**
 * Return public editorial provenance fields for one entry.
 *
 * @return array{source_name: string, source_url: string, reviewer: \WP_User|null, last_reviewed: string, disclosure: string}
 */
function provenance( \WP_Post $post ): array {
	$reviewer_id = absint( get_post_meta( $post->ID, 'alb_reviewer_id', true ) );
	$reviewer    = $reviewer_id > 0 ? get_userdata( $reviewer_id ) : false;

	return array(
		'source_name'   => (string) get_post_meta( $post->ID, 'alb_source_name', true ),
		'source_url'    => (string) get_post_meta( $post->ID, 'alb_source_url', true ),
		'reviewer'      => $reviewer instanceof \WP_User ? $reviewer : null,
		'last_reviewed' => (string) get_post_meta( $post->ID, 'alb_last_reviewed_at', true ),
		'disclosure'    => (string) get_post_meta( $post->ID, 'alb_editorial_disclosure', true ),
	);
}

/**
 * Read and sanitize a public archive filter key.
 */
function request_key( string $name ): string {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public GET filters only read published content.
	$value = isset( $_GET[ $name ] ) && is_string( $_GET[ $name ] ) ? wp_unslash( $_GET[ $name ] ) : '';

	return sanitize_key( $value );
}

/**
 * Preserve valid non-Latin topic slugs while sanitizing the query value.
 */
function request_topic_slug(): string {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public GET filters only read published content.
	$value = isset( $_GET['topic'] ) && is_string( $_GET['topic'] ) ? wp_unslash( $_GET['topic'] ) : '';

	return sanitize_title( $value );
}

/**
 * Return registered post types for one public filter.
 *
 * @return list<string>
 */
function post_types_for_filter( string $type ): array {
	if ( 'news' === $type || ! post_type_exists( 'alb_analysis' ) ) {
		return array( 'post' );
	}

	if ( 'analysis' === $type ) {
		return array( 'alb_analysis' );
	}

	return array( 'post', 'alb_analysis' );
}

/**
 * Count published entries while allowing Polylang to filter the query.
 *
 * @param list<string> $post_types Registered post types.
 */
function published_count( array $post_types ): int {
	$query = new \WP_Query(
		array(
			'post_type'           => $post_types,
			'post_status'         => 'publish',
			'posts_per_page'      => 1,
			'fields'              => 'ids',
			'ignore_sticky_posts' => true,
			'suppress_filters'    => false,
		)
	);

	return (int) $query->found_posts;
}

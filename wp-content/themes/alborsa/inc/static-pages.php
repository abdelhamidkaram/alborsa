<?php
/**
 * Read models and content navigation for selected static pages.
 *
 * @package Alborsa
 */

declare(strict_types=1);

namespace Alborsa\Theme\StaticPages;

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

const META_KEY  = 'alb_static_page_layout';
const MAX_PAGES = 100;

/**
 * Register static page presentation hooks.
 */
function register_hooks(): void {
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_assets' );
}

/**
 * Load the scroll-aware table of contents only on selected pages.
 */
function enqueue_assets(): void {
	if ( ! is_page() || ! is_static_page( get_queried_object_id() ) ) {
		return;
	}

	wp_enqueue_script(
		'alborsa-theme-static-page',
		ALBORSA_THEME_URL . '/assets/js/static-page.js',
		array(),
		\Alborsa\Theme\Assets\asset_version( '/assets/js/static-page.js' ),
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);
}

/**
 * Determine whether a page uses the selected static-page presentation.
 *
 * @param int $post_id WordPress page ID.
 */
function is_static_page( int $post_id ): bool {
	if ( $post_id <= 0 ) {
		return false;
	}

	return in_array( get_post_meta( $post_id, META_KEY, true ), array( true, 1, '1', 'true', 'on' ), true );
}

/**
 * Build the bounded data required by the static page template.
 *
 * @return array{
 *   content: string,
 *   headings: list<array{id: string, label: string}>,
 *   pages: list<array{id: int, label: string, url: string}>
 * }
 *
 * @param \WP_Post $post Current page object.
 */
function page_model( \WP_Post $post ): array {
	$rendered_content = apply_filters( 'the_content', $post->post_content );
	$content_model    = content_with_heading_anchors( is_string( $rendered_content ) ? $rendered_content : '' );

	return array(
		'content'  => $content_model['content'],
		'headings' => $content_model['headings'],
		'pages'    => published_static_pages(),
	);
}

/**
 * Return selected, published pages in the current Polylang language.
 *
 * @return list<array{id: int, label: string, url: string}>
 */
function published_static_pages(): array {
	$query = new \WP_Query( static_page_query_arguments() );
	$pages = array();

	foreach ( $query->posts as $page ) {
		if ( ! $page instanceof \WP_Post ) {
			continue;
		}

		$page_record = static_page_record( $page );
		if ( null !== $page_record ) {
			$pages[] = $page_record;
		}
	}

	return $pages;
}

/**
 * Keep the navigation query bounded and compatible with Polylang filters.
 *
 * @return array<string, mixed>
 */
function static_page_query_arguments(): array {
	return array(
		'post_type'              => 'page',
		'post_status'            => 'publish',
		'posts_per_page'         => MAX_PAGES,
		'orderby'                => array(
			'menu_order' => 'ASC',
			'title'      => 'ASC',
		),
		'no_found_rows'          => true,
		'ignore_sticky_posts'    => true,
		'suppress_filters'       => false,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Bounded navigation query for an explicitly selected page set.
		'meta_query'             => array(
			array(
				'key'     => META_KEY,
				'value'   => '1',
				'compare' => '=',
			),
		),
	);
}

/**
 * Return a public navigation record only when WordPress resolves its URL.
 *
 * @return array{id: int, label: string, url: string}|null
 *
 * @param \WP_Post $page Published page object.
 */
function static_page_record( \WP_Post $page ): ?array {
	$url = get_permalink( $page );

	return is_string( $url )
		? array(
			'id'    => $page->ID,
			'label' => get_the_title( $page ),
			'url'   => $url,
		)
		: null;
}

/**
 * Add stable anchors to H2 elements and return their visible labels.
 *
 * @return array{content: string, headings: list<array{id: string, label: string}>}
 *
 * @param string $content Rendered page content.
 */
function content_with_heading_anchors( string $content ): array {
	$html     = new \WP_HTML_Tag_Processor( $content );
	$headings = array();
	$used_ids = array();
	$sequence = 0;

	while ( $html->next_tag() ) {
		if ( 'H2' !== $html->get_tag() || $html->is_tag_closer() ) {
			continue;
		}

		++$sequence;
		$existing_id = $html->get_attribute( 'id' );
		$heading_id  = unique_heading_id( is_string( $existing_id ) ? $existing_id : '', $sequence, $used_ids );

		$used_ids[ $heading_id ] = true;
		$html->set_attribute( 'id', $heading_id );
		$html->add_class( 'static-page__heading' );

		$heading_text = read_heading_text( $html );

		if ( '' !== $heading_text ) {
			$headings[] = array(
				'id'    => $heading_id,
				'label' => $heading_text,
			);
		}
	}

	return array(
		'content'  => $html->get_updated_html(),
		'headings' => $headings,
	);
}

/**
 * Read visible H2 text while advancing the processor to its closing tag.
 *
 * @param \WP_HTML_Tag_Processor $html Processor positioned at an opening H2 tag.
 */
function read_heading_text( \WP_HTML_Tag_Processor $html ): string {
	$heading_text = '';

	while ( $html->next_token() ) {
		if ( 'H2' === $html->get_tag() && $html->is_tag_closer() ) {
			break;
		}

		if ( '#text' === $html->get_token_type() ) {
			$heading_text .= $html->get_modifiable_text();
		}
	}

	$heading_text = html_entity_decode( $heading_text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$heading_text = preg_replace( '/[\s\p{Z}]+/u', ' ', wp_strip_all_tags( $heading_text ) ) ?? '';

	return trim( $heading_text );
}

/**
 * Preserve a valid author-provided anchor or generate a deterministic fallback.
 *
 * @param string              $candidate Existing heading anchor.
 * @param int                 $sequence  One-based H2 position.
 * @param array<string, true> $used_ids Previously assigned IDs.
 */
function unique_heading_id( string $candidate, int $sequence, array $used_ids ): string {
	$candidate  = trim( $candidate );
	$is_valid   = '' !== $candidate && 1 === preg_match( '/\A[^\s"\'<>`#%]+\z/u', $candidate );
	$base       = $is_valid ? $candidate : 'alborsa-section-' . $sequence;
	$heading_id = $base;
	$suffix     = 2;

	while ( isset( $used_ids[ $heading_id ] ) ) {
		$heading_id = $base . '-' . $suffix;
		++$suffix;
	}

	return $heading_id;
}

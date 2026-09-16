<?php
/**
 * Presentation helpers shared by theme templates.
 *
 * @package Alborsa
 */

declare(strict_types=1);

namespace Alborsa\Theme\TemplateTags;

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the navigation contract used by fallbacks and the mobile dock.
 *
 * @return array<string, array{label: string, url: string}>
 */
function navigation_items(): array {
	return array(
		'home'      => array(
			'label' => __( 'الرئيسية', 'alborsa' ),
			'url'   => home_url( '/' ),
		),
		'markets'   => array(
			'label' => __( 'الأسواق', 'alborsa' ),
			'url'   => section_url( 'aswaq' ),
		),
		'news'      => array(
			'label' => __( 'الأخبار', 'alborsa' ),
			'url'   => news_url(),
		),
		'brokers'   => array(
			'label' => __( 'الوسطاء', 'alborsa' ),
			'url'   => section_url( 'brokers' ),
		),
		'tools'     => array(
			'label' => __( 'الأدوات', 'alborsa' ),
			'url'   => section_url( 'adawat' ),
		),
		'analysis'  => array(
			'label' => __( 'التحليل', 'alborsa' ),
			'url'   => post_type_archive_url( 'alb_analysis', 'tahleel' ),
		),
		'education' => array(
			'label' => __( 'التعليم', 'alborsa' ),
			'url'   => post_type_archive_url( 'alb_guide', 'taleem' ),
		),
	);
}

/**
 * Render the fallback primary navigation when no menu is assigned.
 *
 * @param string $menu_class CSS class used by the target navigation.
 */
function primary_menu_fallback( string $menu_class = 'primary-menu' ): void {
	?>
	<ul class="<?php echo esc_attr( $menu_class ); ?>">
		<?php foreach ( navigation_items() as $key => $navigation_item ) : ?>
			<li class="menu-item menu-item--<?php echo esc_attr( $key ); ?>">
				<a href="<?php echo esc_url( $navigation_item['url'] ); ?>"><?php echo esc_html( $navigation_item['label'] ); ?></a>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}

/**
 * Return the configured posts page or the expected news section URL.
 */
function news_url(): string {
	$posts_page_id = (int) get_option( 'page_for_posts' );

	if ( $posts_page_id > 0 ) {
		$posts_page_url = get_permalink( $posts_page_id );

		if ( is_string( $posts_page_url ) ) {
			return $posts_page_url;
		}
	}

	return section_url( 'akhabar' );
}

/**
 * Return a post type archive URL or its approved fallback path.
 *
 * @param string $post_type     Registered post type name.
 * @param string $fallback_path Relative path used before the post type exists.
 */
function post_type_archive_url( string $post_type, string $fallback_path ): string {
	$archive_url = get_post_type_archive_link( $post_type );

	return is_string( $archive_url ) ? $archive_url : section_url( $fallback_path );
}

/**
 * Resolve an existing page before falling back to a WordPress-built URL.
 *
 * @param string $path Relative page path without a language prefix.
 */
function section_url( string $path ): string {
	$page = get_page_by_path( $path );

	if ( $page instanceof \WP_Post ) {
		$page_url = get_permalink( $page );

		if ( is_string( $page_url ) ) {
			return $page_url;
		}
	}

	return home_url( user_trailingslashit( $path ) );
}

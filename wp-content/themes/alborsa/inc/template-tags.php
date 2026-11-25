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
 * Render the official Alborsa logo linked to the home page.
 */
function site_logo(): void {
	?>
	<a class="site-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" aria-label="<?php esc_attr_e( 'البورصة — الرئيسية', 'alborsa' ); ?>">
		<img class="site-logo__image site-logo__image--light" src="<?php echo esc_url( get_theme_file_uri( 'assets/images/alborsa-logo.svg' ) ); ?>" width="123" height="44" alt="" decoding="async">
		<img class="site-logo__image site-logo__image--dark" src="<?php echo esc_url( get_theme_file_uri( 'assets/images/alborsa-logo-dark.svg' ) ); ?>" width="123" height="44" alt="" decoding="async">
		<img class="site-logo__mark site-logo__mark--light" src="<?php echo esc_url( get_theme_file_uri( 'assets/images/alborsa-mark.svg' ) ); ?>" width="25" height="26" alt="" decoding="async">
		<img class="site-logo__mark site-logo__mark--dark" src="<?php echo esc_url( get_theme_file_uri( 'assets/images/alborsa-mark-dark.svg' ) ); ?>" width="25" height="26" alt="" decoding="async">
	</a>
	<?php
}

/**
 * Return the navigation contract used by the global navigation fallbacks.
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
 * Render one footer navigation column with a design-matched fallback.
 *
 * @param string                                           $location       Registered menu location.
 * @param array<string, array{label: string, url: string}> $fallback_links Fallback links.
 */
function footer_navigation( string $location, array $fallback_links ): void {
	if ( has_nav_menu( $location ) ) {
		wp_nav_menu(
			array(
				'theme_location' => $location,
				'container'      => false,
				'menu_class'     => 'footer-links',
				'fallback_cb'    => false,
				'depth'          => 1,
			)
		);

		return;
	}
	?>
	<ul class="footer-links">
		<?php foreach ( $fallback_links as $footer_link ) : ?>
			<li><a href="<?php echo esc_url( $footer_link['url'] ); ?>"><?php echo esc_html( $footer_link['label'] ); ?></a></li>
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

	return section_url( 'news' );
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

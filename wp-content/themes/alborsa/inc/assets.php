<?php
/**
 * Frontend asset registration.
 *
 * @package Alborsa
 */

declare(strict_types=1);

namespace Alborsa\Theme\Assets;

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register asset hooks.
 */
function register_hooks(): void {
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_assets' );
}

/**
 * Enqueue the Figma design-system foundation and frontend behavior.
 */
function enqueue_assets(): void {
	wp_enqueue_style(
		'alborsa-theme',
		get_stylesheet_uri(),
		array(),
		ALBORSA_THEME_VERSION
	);

	wp_enqueue_style(
		'alborsa-theme-tokens',
		ALBORSA_THEME_URL . '/assets/css/tokens.css',
		array( 'alborsa-theme' ),
		asset_version( '/assets/css/tokens.css' )
	);

	wp_enqueue_style(
		'alborsa-theme-main',
		ALBORSA_THEME_URL . '/assets/css/main.css',
		array( 'alborsa-theme-tokens' ),
		asset_version( '/assets/css/main.css' )
	);

	wp_enqueue_style(
		'alborsa-theme-mobile-system',
		ALBORSA_THEME_URL . '/assets/css/mobile-system.css',
		array( 'alborsa-theme-main' ),
		asset_version( '/assets/css/mobile-system.css' )
	);

	if ( is_home() || is_page( 'akhabar' ) || is_singular( 'post' ) ) {
		wp_enqueue_style(
			'alborsa-theme-news',
			ALBORSA_THEME_URL . '/assets/css/news.css',
			array( 'alborsa-theme-mobile-system' ),
			asset_version( '/assets/css/news.css' )
		);
	}

	wp_enqueue_script(
		'alborsa-theme-preferences',
		ALBORSA_THEME_URL . '/assets/js/theme.js',
		array(),
		asset_version( '/assets/js/theme.js' ),
		array(
			'in_footer' => false,
		)
	);

	wp_localize_script(
		'alborsa-theme-preferences',
		'alborsaTheme',
		array(
			'darkMode'  => __( 'تفعيل الوضع الداكن', 'alborsa' ),
			'lightMode' => __( 'تفعيل الوضع الفاتح', 'alborsa' ),
		)
	);

	if ( is_singular( 'post' ) ) {
		wp_enqueue_script(
			'alborsa-theme-news',
			ALBORSA_THEME_URL . '/assets/js/news.js',
			array(),
			asset_version( '/assets/js/news.js' ),
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_localize_script(
			'alborsa-theme-news',
			'alborsaNews',
			array(
				'shared'     => __( 'تمت مشاركة المقال', 'alborsa' ),
				'copied'     => __( 'تم نسخ رابط المقال', 'alborsa' ),
				'copyFailed' => __( 'تعذر نسخ الرابط. انسخه من شريط العنوان.', 'alborsa' ),
			)
		);
	}

	wp_enqueue_script(
		'alborsa-theme-navigation',
		ALBORSA_THEME_URL . '/assets/js/navigation.js',
		array( 'alborsa-theme-preferences' ),
		asset_version( '/assets/js/navigation.js' ),
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);

	wp_localize_script(
		'alborsa-theme-navigation',
		'alborsaNavigation',
		array(
			'closeMenu'   => __( 'إغلاق القائمة الرئيسية', 'alborsa' ),
			'openMenu'    => __( 'فتح القائمة الرئيسية', 'alborsa' ),
			'pauseTicker' => __( 'إيقاف حركة شريط الأسعار', 'alborsa' ),
			'playTicker'  => __( 'تشغيل حركة شريط الأسعار', 'alborsa' ),
		)
	);

	wp_enqueue_script(
		'alborsa-theme-platform',
		ALBORSA_THEME_URL . '/assets/js/platform.js',
		array(),
		asset_version( '/assets/js/platform.js' ),
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);

	wp_localize_script(
		'alborsa-theme-platform',
		'alborsaPlatform',
		array(
			'restBase' => esc_url_raw( rest_url( 'alborsa/v1/' ) ),
			'strings'  => array(
				'anonymousUser'     => __( 'مستخدم', 'alborsa' ),
				'unavailable'       => __( 'غير متاح', 'alborsa' ),
				'fresh'             => __( 'محدّث من المصدر', 'alborsa' ),
				'delayed'           => __( 'بيانات متأخرة', 'alborsa' ),
				'stale'             => __( 'بيانات قديمة', 'alborsa' ),
				'loading'           => __( 'جارٍ التحميل…', 'alborsa' ),
				'minuteShort'       => __( 'د', 'alborsa' ),
				'noReviews'         => __( 'لا توجد تقييمات منشورة', 'alborsa' ),
				'ratingScale'       => __( '/ 5', 'alborsa' ),
				'requestError'      => __( 'تعذر إكمال الطلب الآن. حاول مرة أخرى.', 'alborsa' ),
				'invalidCalculator' => __( 'راجع القيم المدخلة ثم أعد المحاولة.', 'alborsa' ),
				'chooseBrokers'     => __( 'اختر وسيطين أو ثلاثة للمقارنة.', 'alborsa' ),
				'maximumBrokers'    => __( 'يمكن مقارنة ثلاثة وسطاء كحد أقصى.', 'alborsa' ),
				'noData'            => __( 'لم تُنشر بيانات كافية بعد.', 'alborsa' ),
			),
		)
	);
}

/**
 * Return a cache-safe version for a theme asset.
 *
 * @param string $relative_path Root-relative path below the theme directory.
 */
function asset_version( string $relative_path ): string {
	$absolute_path = ALBORSA_THEME_PATH . $relative_path;
	$modified      = file_exists( $absolute_path ) ? filemtime( $absolute_path ) : false;

	return false === $modified ? ALBORSA_THEME_VERSION : (string) $modified;
}

<?php
/**
 * Unit test bootstrap.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'ALBORSA_PLATFORM_FILE', dirname( __DIR__ ) . '/alborsa-platform.php' );
define( 'ALBORSA_PLATFORM_PATH', dirname( __DIR__ ) . '/' );

require_once ALBORSA_PLATFORM_PATH . 'vendor/autoload.php';
require_once __DIR__ . '/Support/WPErrorDouble.php';
require_once __DIR__ . '/Support/WPPostDouble.php';
require_once __DIR__ . '/Support/WPRestRequestDouble.php';
require_once __DIR__ . '/Support/WPTermDouble.php';
require_once __DIR__ . '/Support/WpdbDouble.php';

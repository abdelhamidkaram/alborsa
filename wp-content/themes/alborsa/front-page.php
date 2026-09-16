<?php
/**
 * Editorial Signal homepage.
 *
 * @package Alborsa
 */

declare(strict_types=1);

use function Alborsa\Theme\HomeContent\published_posts;
use function Alborsa\Theme\MarketStatus\assets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$latest_news   = published_posts( 'post', 6 );
$lead_story    = array_shift( $latest_news );
$breaking_news = array_slice( $latest_news, 0, 5 );
$analyses      = published_posts( 'alb_analysis', 3 );
$guides        = published_posts( 'alb_guide', 4 );
$market_assets = assets();

get_header();

get_template_part(
	'template-parts/home/editorial-signal',
	null,
	array(
		'lead_story' => $lead_story,
	)
);

get_template_part(
	'template-parts/home/breaking-news',
	null,
	array(
		'news' => $breaking_news,
	)
);
?>
<div class="site-container home-main-grid">
	<?php
	get_template_part(
		'template-parts/home/market-overview',
		null,
		array(
			'assets' => $market_assets,
		)
	);

	get_template_part(
		'template-parts/home/analysis',
		null,
		array(
			'analyses' => $analyses,
		)
	);
	?>
</div>
<?php
get_template_part( 'template-parts/home/broker-spotlight' );

get_template_part(
	'template-parts/home/education',
	null,
	array(
		'guides' => $guides,
	)
);

get_template_part( 'template-parts/home/newsletter' );

get_footer();

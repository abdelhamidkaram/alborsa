<?php
/**
 * Singular financial asset template.
 *
 * @package Alborsa
 */

declare(strict_types=1);

use function Alborsa\Theme\Content\term_names;
use function Alborsa\Theme\TemplateTags\section_url;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$asset_id         = get_the_ID();
	$symbol           = (string) get_post_meta( $asset_id, 'alb_canonical_symbol', true );
	$base_currency    = (string) get_post_meta( $asset_id, 'alb_base_currency', true );
	$quote_currency   = (string) get_post_meta( $asset_id, 'alb_quote_currency', true );
	$isin             = (string) get_post_meta( $asset_id, 'alb_isin', true );
	$official_website = (string) get_post_meta( $asset_id, 'alb_official_website', true );
	$listing_status   = (string) get_post_meta( $asset_id, 'alb_listing_status', true );
	$asset_types      = term_names( $asset_id, 'alb_asset_type' );
	$exchanges        = term_names( $asset_id, 'alb_exchange' );
	$sectors          = term_names( $asset_id, 'alb_sector' );
	?>
	<article <?php post_class( 'asset-profile' ); ?>>
		<header class="asset-profile__header">
			<div class="site-container asset-profile__headline">
				<div>
					<p class="eyebrow"><?php esc_html_e( 'ملف أصل مالي', 'alborsa' ); ?></p>
					<h1><?php the_title(); ?></h1>
					<?php if ( '' !== $symbol ) : ?>
						<p class="asset-profile__symbol" dir="ltr"><?php echo esc_html( $symbol ); ?></p>
					<?php endif; ?>
				</div>
				<a class="back-link" href="<?php echo esc_url( section_url( 'aswaq' ) ); ?>"><?php esc_html_e( 'العودة إلى الأسواق', 'alborsa' ); ?></a>
			</div>
		</header>

		<div class="site-container asset-profile__grid">
			<section class="quote-state" aria-labelledby="quote-state-title" data-alborsa-quote="<?php echo esc_attr( (string) $asset_id ); ?>">
				<div>
					<p class="eyebrow"><?php esc_html_e( 'بيانات السوق', 'alborsa' ); ?></p>
					<h2 id="quote-state-title" data-quote-price><?php esc_html_e( 'السعر غير متاح حالياً', 'alborsa' ); ?></h2>
					<p class="quote-state__change"><?php esc_html_e( 'التغيير:', 'alborsa' ); ?> <strong data-quote-change>—</strong></p>
					<dl class="quote-state__meta">
						<div><dt><?php esc_html_e( 'حالة اللقطة', 'alborsa' ); ?></dt><dd data-quote-status><?php esc_html_e( 'بانتظار المصدر', 'alborsa' ); ?></dd></div>
						<div><dt><?php esc_html_e( 'وقت السوق', 'alborsa' ); ?></dt><dd data-quote-as-of>—</dd></div>
						<div><dt><?php esc_html_e( 'المصدر', 'alborsa' ); ?></dt><dd data-quote-source>—</dd></div>
					</dl>
				</div>
				<p class="quote-state__notice"><?php esc_html_e( 'هذه لقطة متزامنة وليست بالضرورة لحظية. راجع حالة التأخير ووقت السوق قبل استخدامها.', 'alborsa' ); ?></p>
			</section>

			<section class="asset-facts" aria-labelledby="asset-facts-title">
				<h2 id="asset-facts-title"><?php esc_html_e( 'بيانات التعريف', 'alborsa' ); ?></h2>
				<dl>
					<?php if ( '' !== $asset_types ) : ?>
						<div><dt><?php esc_html_e( 'نوع الأصل', 'alborsa' ); ?></dt><dd><?php echo esc_html( $asset_types ); ?></dd></div>
					<?php endif; ?>
					<?php if ( '' !== $exchanges ) : ?>
						<div><dt><?php esc_html_e( 'السوق', 'alborsa' ); ?></dt><dd><?php echo esc_html( $exchanges ); ?></dd></div>
					<?php endif; ?>
					<?php if ( '' !== $sectors ) : ?>
						<div><dt><?php esc_html_e( 'القطاع', 'alborsa' ); ?></dt><dd><?php echo esc_html( $sectors ); ?></dd></div>
					<?php endif; ?>
					<?php if ( '' !== $base_currency ) : ?>
						<div><dt><?php esc_html_e( 'عملة الأساس', 'alborsa' ); ?></dt><dd dir="ltr"><?php echo esc_html( $base_currency ); ?></dd></div>
					<?php endif; ?>
					<?php if ( '' !== $quote_currency ) : ?>
						<div><dt><?php esc_html_e( 'عملة التسعير', 'alborsa' ); ?></dt><dd dir="ltr"><?php echo esc_html( $quote_currency ); ?></dd></div>
					<?php endif; ?>
					<?php if ( '' !== $isin ) : ?>
						<div><dt>ISIN</dt><dd dir="ltr"><?php echo esc_html( $isin ); ?></dd></div>
					<?php endif; ?>
					<?php if ( '' !== $listing_status ) : ?>
						<div><dt><?php esc_html_e( 'حالة الإدراج', 'alborsa' ); ?></dt><dd><?php echo esc_html( $listing_status ); ?></dd></div>
					<?php endif; ?>
					<?php if ( '' !== $official_website ) : ?>
						<div><dt><?php esc_html_e( 'الموقع الرسمي', 'alborsa' ); ?></dt><dd><a href="<?php echo esc_url( $official_website ); ?>" rel="noopener noreferrer"><?php esc_html_e( 'زيارة المصدر الرسمي', 'alborsa' ); ?></a></dd></div>
					<?php endif; ?>
				</dl>
			</section>

			<?php if ( '' !== trim( (string) get_the_content() ) ) : ?>
				<div class="entry-content asset-profile__content">
					<?php the_content(); ?>
				</div>
			<?php endif; ?>
		</div>
	</article>
	<?php
endwhile;

get_footer();

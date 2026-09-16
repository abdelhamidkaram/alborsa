<?php
/**
 * Markets landing page.
 *
 * @package Alborsa
 */

declare(strict_types=1);

use function Alborsa\Theme\Content\count_assets;
use function Alborsa\Theme\Content\published_assets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$asset_records = published_assets();
$market_groups = array(
	array(
		'name'  => __( 'السوق السعودي', 'alborsa' ),
		'note'  => __( 'تداول ومؤشر TASI', 'alborsa' ),
		'count' => count_assets( $asset_records, array( 'tadawul', 'saudi-exchange' ), 'exchanges' ),
	),
	array(
		'name'  => __( 'أسواق الإمارات', 'alborsa' ),
		'note'  => __( 'DFM وADX', 'alborsa' ),
		'count' => count_assets( $asset_records, array( 'dfm', 'adx' ), 'exchanges' ),
	),
	array(
		'name'  => __( 'الفوركس', 'alborsa' ),
		'note'  => __( 'أزواج الخليج والأزواج العالمية', 'alborsa' ),
		'count' => count_assets( $asset_records, array( 'forex' ), 'types' ),
	),
	array(
		'name'  => __( 'السلع', 'alborsa' ),
		'note'  => __( 'النفط والذهب والفضة', 'alborsa' ),
		'count' => count_assets( $asset_records, array( 'commodity', 'commodities' ), 'types' ),
	),
	array(
		'name'  => __( 'العملات الرقمية', 'alborsa' ),
		'note'  => __( 'BTC وETH وBNB', 'alborsa' ),
		'count' => count_assets( $asset_records, array( 'crypto', 'cryptocurrency' ), 'types' ),
	),
);

get_header();
?>
<section class="section-hero section-hero--markets">
	<div class="site-container section-hero__inner section-hero__inner--split">
		<div>
			<p class="eyebrow"><?php esc_html_e( 'خريطة السوق', 'alborsa' ); ?></p>
			<h1><?php esc_html_e( 'الأسواق', 'alborsa' ); ?></h1>
			<p><?php esc_html_e( 'مدخل منظم للأسهم الخليجية والفوركس والسلع والعملات الرقمية، مع إظهار حالة البيانات بوضوح.', 'alborsa' ); ?></p>
		</div>
		<div class="hero-state">
			<span class="status-dot status-dot--unavailable" aria-hidden="true"></span>
			<strong><?php esc_html_e( 'الأسعار غير متصلة بعد', 'alborsa' ); ?></strong>
			<p><?php esc_html_e( 'سجل الأصول يعمل، بينما السعر والحجم والتغيير ينتظرون مزوداً مرخصاً.', 'alborsa' ); ?></p>
		</div>
	</div>
</section>

<div class="site-container section-content section-stack">
	<section aria-labelledby="market-groups-title">
		<header class="section-heading">
			<div>
				<p class="eyebrow"><?php esc_html_e( 'نطاق التغطية', 'alborsa' ); ?></p>
				<h2 id="market-groups-title"><?php esc_html_e( 'أسواق تهم المستثمر الخليجي', 'alborsa' ); ?></h2>
			</div>
		</header>
		<div class="status-card-grid status-card-grid--five">
			<?php foreach ( $market_groups as $market_group ) : ?>
				<article class="status-card">
					<p class="status-card__count"><?php echo esc_html( (string) $market_group['count'] ); ?></p>
					<h3><?php echo esc_html( $market_group['name'] ); ?></h3>
					<p><?php echo esc_html( $market_group['note'] ); ?></p>
					<span><?php esc_html_e( 'أصل منشور', 'alborsa' ); ?></span>
				</article>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="asset-registry" aria-labelledby="asset-registry-title">
		<header class="section-heading">
			<div>
				<p class="eyebrow"><?php esc_html_e( 'السجل المنشور', 'alborsa' ); ?></p>
				<h2 id="asset-registry-title"><?php esc_html_e( 'الأصول المالية', 'alborsa' ); ?></h2>
				<p><?php esc_html_e( 'هذه بيانات تعريفية فقط. لا تُعرض أسعار قبل توثيق المصدر ووقت التحديث ودرجة التأخير.', 'alborsa' ); ?></p>
			</div>
		</header>

		<?php if ( array() !== $asset_records ) : ?>
			<div class="data-table" role="table" aria-label="<?php esc_attr_e( 'سجل الأصول المالية المنشورة', 'alborsa' ); ?>">
				<div class="data-table__row data-table__head" role="row">
					<span role="columnheader"><?php esc_html_e( 'الأصل', 'alborsa' ); ?></span>
					<span role="columnheader"><?php esc_html_e( 'النوع / السوق', 'alborsa' ); ?></span>
					<span role="columnheader"><?php esc_html_e( 'العملة', 'alborsa' ); ?></span>
					<span role="columnheader"><?php esc_html_e( 'السعر', 'alborsa' ); ?></span>
				</div>
				<?php foreach ( $asset_records as $asset_record ) : ?>
					<div class="data-table__row" role="row">
						<a class="data-table__asset" role="cell" href="<?php echo esc_url( $asset_record['url'] ); ?>">
							<strong dir="ltr"><?php echo esc_html( '' !== $asset_record['symbol'] ? $asset_record['symbol'] : '—' ); ?></strong>
							<span><?php echo esc_html( $asset_record['title'] ); ?></span>
						</a>
						<span role="cell"><?php echo esc_html( trim( $asset_record['type_labels'] . ( '' !== $asset_record['exchange_labels'] ? ' / ' . $asset_record['exchange_labels'] : '' ) ) ); ?></span>
						<span role="cell" dir="ltr"><?php echo esc_html( '' !== $asset_record['quote_currency'] ? $asset_record['quote_currency'] : '—' ); ?></span>
						<span class="availability-tag" role="cell"><i class="status-dot status-dot--unavailable" aria-hidden="true"></i><?php esc_html_e( 'غير متاح', 'alborsa' ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<div class="empty-state">
				<h3><?php esc_html_e( 'لم تُنشر أصول بعد', 'alborsa' ); ?></h3>
				<p><?php esc_html_e( 'يبدأ الجدول بالظهور بعد استيراد سجل الأصول ومراجعته.', 'alborsa' ); ?></p>
			</div>
		<?php endif; ?>
	</section>

	<section class="availability-panel" aria-labelledby="market-tools-title">
		<div>
			<p class="eyebrow"><?php esc_html_e( 'مكونات مرتبطة بالأسواق', 'alborsa' ); ?></p>
			<h2 id="market-tools-title"><?php esc_html_e( 'التقويم والمراقبة والفلترة', 'alborsa' ); ?></h2>
		</div>
		<ul class="availability-list">
			<li><strong><?php esc_html_e( 'التقويم الاقتصادي', 'alborsa' ); ?></strong><span><?php esc_html_e( 'ينتظر مصدر أحداث موثقاً للسعودية والإمارات.', 'alborsa' ); ?></span></li>
			<li><strong><?php esc_html_e( 'فلتر الأسهم', 'alborsa' ); ?></strong><span><?php esc_html_e( 'ينتظر بيانات أساسيات مرخصة مثل P/E والقيمة السوقية.', 'alborsa' ); ?></span></li>
			<li><strong><?php esc_html_e( 'قائمة المراقبة وتنبيهات الأسعار', 'alborsa' ); ?></strong><span><?php esc_html_e( 'ضمن مرحلة حسابات المستخدمين اللاحقة.', 'alborsa' ); ?></span></li>
		</ul>
	</section>
</div>
<?php
get_footer();

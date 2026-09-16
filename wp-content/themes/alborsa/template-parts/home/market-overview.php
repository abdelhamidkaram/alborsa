<?php
/**
 * Homepage market registry overview and volume-data state.
 *
 * @package Alborsa
 */

declare(strict_types=1);

use function Alborsa\Theme\TemplateTags\section_url;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$market_assets = isset( $args['assets'] ) && is_array( $args['assets'] ) ? $args['assets'] : array();
?>
<section class="home-module home-market" aria-labelledby="home-market-title">
	<header class="module-heading">
		<div>
			<h2 id="home-market-title"><?php esc_html_e( 'الأسواق', 'alborsa' ); ?></h2>
			<p><?php esc_html_e( 'أصول منشورة من سجل المنصة؛ الأسعار تنتظر مصدر بيانات معتمداً.', 'alborsa' ); ?></p>
		</div>
		<a href="<?php echo esc_url( section_url( 'aswaq' ) ); ?>"><?php esc_html_e( 'عرض الأسواق', 'alborsa' ); ?></a>
	</header>

	<?php if ( array() !== $market_assets ) : ?>
		<div class="market-table" role="table" aria-label="<?php esc_attr_e( 'حالة أصول السوق', 'alborsa' ); ?>">
			<div class="market-table__row market-table__head" role="row">
				<span role="columnheader"><?php esc_html_e( 'الأصل', 'alborsa' ); ?></span>
				<span role="columnheader"><?php esc_html_e( 'حالة السعر', 'alborsa' ); ?></span>
			</div>
			<?php foreach ( $market_assets as $market_asset ) : ?>
				<div class="market-table__row" role="row" data-alborsa-quote="<?php echo esc_attr( (string) $market_asset['id'] ); ?>">
					<a role="cell" href="<?php echo esc_url( $market_asset['url'] ); ?>">
						<strong><?php echo esc_html( '' !== $market_asset['symbol'] ? $market_asset['symbol'] : $market_asset['title'] ); ?></strong>
						<span><?php echo esc_html( $market_asset['title'] ); ?></span>
					</a>
					<span class="market-table__quote" role="cell"><strong data-quote-price><?php esc_html_e( 'غير متاح', 'alborsa' ); ?></strong><small data-quote-status><?php esc_html_e( 'بانتظار المصدر', 'alborsa' ); ?></small></span>
				</div>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<p class="module-empty"><?php esc_html_e( 'لم تُنشر أصول مالية بعد. يبدأ هذا القسم بالظهور بعد تجهيز سجل الأسواق.', 'alborsa' ); ?></p>
	<?php endif; ?>

	<div class="market-ranking-state">
		<div>
			<h3><?php esc_html_e( 'الأصول الأكثر تداولاً', 'alborsa' ); ?></h3>
			<p><?php esc_html_e( 'الترتيب غير متاح حالياً لأنه يعتمد على حجم تداول موثق من مزود السوق.', 'alborsa' ); ?></p>
		</div>
		<span><?php esc_html_e( 'بانتظار البيانات', 'alborsa' ); ?></span>
	</div>
</section>

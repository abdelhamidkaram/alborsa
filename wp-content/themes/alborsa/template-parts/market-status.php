<?php
/**
 * Global market-status ribbon.
 *
 * @package Alborsa
 */

declare(strict_types=1);

use function Alborsa\Theme\MarketStatus\assets;
use function Alborsa\Theme\TemplateTags\section_url;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$market_assets = assets();
?>
<section class="market-ribbon" aria-labelledby="market-ribbon-title">
	<div class="site-container market-ribbon__inner">
		<div class="market-ribbon__heading">
			<p id="market-ribbon-title"><?php esc_html_e( 'حالة الأسواق', 'alborsa' ); ?></p>
			<a href="<?php echo esc_url( section_url( 'aswaq' ) ); ?>"><?php esc_html_e( 'عرض الأسواق', 'alborsa' ); ?></a>
		</div>
		<?php if ( array() !== $market_assets ) : ?>
			<ul class="market-ribbon__list" aria-label="<?php esc_attr_e( 'الأصول المتاحة في سجل السوق', 'alborsa' ); ?>">
				<?php foreach ( $market_assets as $market_asset ) : ?>
					<li>
						<a href="<?php echo esc_url( $market_asset['url'] ); ?>" data-alborsa-quote="<?php echo esc_attr( (string) $market_asset['id'] ); ?>">
							<strong><?php echo esc_html( '' !== $market_asset['symbol'] ? $market_asset['symbol'] : $market_asset['title'] ); ?></strong>
							<span data-quote-price><?php esc_html_e( 'السعر غير متاح', 'alborsa' ); ?></span>
							<small data-quote-status><?php esc_html_e( 'غير متاح', 'alborsa' ); ?></small>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p class="market-ribbon__empty">
				<span class="status-dot status-dot--unavailable" aria-hidden="true"></span>
				<?php esc_html_e( 'بيانات السوق غير متاحة حالياً؛ سيُفعّل الشريط بعد ربط مصدر معتمد.', 'alborsa' ); ?>
			</p>
		<?php endif; ?>
	</div>
</section>

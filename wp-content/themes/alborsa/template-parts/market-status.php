<?php
/**
 * Market ticker displayed above the primary navigation.
 *
 * @package Alborsa
 */

declare(strict_types=1);

use function Alborsa\Theme\MarketStatus\assets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$market_assets = assets();
?>
<section class="market-ticker" aria-labelledby="market-ticker-title" data-market-ticker data-ticker-state="running">
	<h2 id="market-ticker-title" class="screen-reader-text"><?php esc_html_e( 'أسعار السوق', 'alborsa' ); ?></h2>
	<p class="market-ticker__summary">
		<span><?php esc_html_e( 'آخر تحديث', 'alborsa' ); ?></span>
		<time data-market-ticker-time>—</time>
	</p>

	<?php if ( array() !== $market_assets ) : ?>
		<div class="market-ticker__viewport">
			<div class="market-ticker__track">
				<?php for ( $group_index = 0; $group_index < 2; $group_index++ ) : ?>
					<?php if ( 0 === $group_index ) : ?>
						<ul class="market-ticker__group" aria-label="<?php esc_attr_e( 'الأصول المتاحة في سجل السوق', 'alborsa' ); ?>">
					<?php else : ?>
						<ul class="market-ticker__group" aria-hidden="true" inert>
					<?php endif; ?>
						<?php foreach ( $market_assets as $market_asset ) : ?>
							<li>
								<a href="<?php echo esc_url( $market_asset['url'] ); ?>" data-alborsa-quote="<?php echo esc_attr( (string) $market_asset['id'] ); ?>">
									<strong dir="auto"><?php echo esc_html( '' !== $market_asset['symbol'] ? $market_asset['symbol'] : $market_asset['title'] ); ?></strong>
									<span class="market-ticker__price" dir="ltr" data-quote-price>—</span>
									<span class="market-ticker__change" dir="ltr" data-quote-change>—</span>
									<small class="screen-reader-text" data-quote-status><?php esc_html_e( 'غير متاح', 'alborsa' ); ?></small>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endfor; ?>
			</div>
		</div>

		<button class="market-ticker__toggle" type="button" aria-pressed="false" aria-label="<?php esc_attr_e( 'إيقاف حركة شريط الأسعار', 'alborsa' ); ?>" data-ticker-toggle>
			<svg class="market-ticker__pause" aria-hidden="true" width="18" height="18" viewBox="0 0 18 18" fill="none">
				<path d="M6 4v10M12 4v10" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
			</svg>
			<svg class="market-ticker__play" aria-hidden="true" width="18" height="18" viewBox="0 0 18 18" fill="none">
				<path d="m6 4 8 5-8 5V4Z" fill="currentColor" />
			</svg>
		</button>
	<?php else : ?>
		<p class="market-ticker__empty"><?php esc_html_e( 'بيانات السوق غير متاحة حالياً.', 'alborsa' ); ?></p>
	<?php endif; ?>
</section>

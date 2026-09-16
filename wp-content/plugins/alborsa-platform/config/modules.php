<?php
/**
 * Registered platform modules, in boot order.
 *
 * @package AlborsaPlatform
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

use Alborsa\Platform\Foundation\FoundationModule;
use Alborsa\Platform\Modules\Assets\AssetsModule;
use Alborsa\Platform\Modules\Brokers\BrokersModule;
use Alborsa\Platform\Modules\Editorial\EditorialModule;
use Alborsa\Platform\Modules\FinancialTools\FinancialToolsModule;
use Alborsa\Platform\Modules\MarketData\MarketDataModule;

return array(
	new FoundationModule(),
	new EditorialModule(),
	new AssetsModule(),
	new MarketDataModule(),
	new BrokersModule(),
	new FinancialToolsModule(),
);

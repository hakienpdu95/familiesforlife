<?php

namespace Modules\AccessTrade\Console\Commands;

use Illuminate\Console\Command;
use Modules\AccessTrade\Features\Sync\Actions\SyncOffersAction;
use Modules\AccessTrade\Features\Sync\Actions\SyncTopProductsAction;

/**
 * Usage:
 *   php artisan accesstrade:sync
 *   php artisan accesstrade:sync --offers-only
 *   php artisan accesstrade:sync --products-only
 *
 * Chạy định kỳ qua routes/console.php (Schedule::command('accesstrade:sync')).
 */
class SyncAccessTradeCommand extends Command
{
    protected $signature = 'accesstrade:sync
                            {--offers-only : Chỉ đồng bộ vouchers/coupons/deals + khuyến mãi}
                            {--products-only : Chỉ đồng bộ top sản phẩm bán chạy}';

    protected $description = 'Đồng bộ offers (voucher/coupon/khuyến mãi) và top sản phẩm bán chạy từ AccessTrade Publisher API';

    public function handle(SyncOffersAction $syncOffers, SyncTopProductsAction $syncTopProducts): int
    {
        $onlyOffers   = (bool) $this->option('offers-only');
        $onlyProducts = (bool) $this->option('products-only');

        if (! $onlyProducts) {
            $this->info('Đồng bộ offers (voucher/coupon/khuyến mãi)...');
            $result = $syncOffers->handle();
            $this->info("  ✓ Đã đồng bộ {$result['synced']} offer, {$result['failed']} merchant lỗi.");
        }

        if (! $onlyOffers) {
            $this->info('Đồng bộ top sản phẩm bán chạy...');
            $result = $syncTopProducts->handle();
            $this->info("  ✓ Đã đồng bộ {$result['synced']} sản phẩm, {$result['failed']} merchant lỗi.");
        }

        return self::SUCCESS;
    }
}

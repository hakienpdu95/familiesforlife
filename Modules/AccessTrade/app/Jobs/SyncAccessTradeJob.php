<?php

namespace Modules\AccessTrade\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\AccessTrade\Features\Sync\Actions\SyncOffersAction;
use Modules\AccessTrade\Features\Sync\Actions\SyncTopProductsAction;

/** Dispatch khi admin bấm "Đồng bộ ngay" ở dashboard/accesstrade — cùng logic dùng bởi accesstrade:sync. */
class SyncAccessTradeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Worst case: offers.max_pages (mặc định 50) trang tuần tự, mỗi trang tới 15s (timeout của
     * AccessTradeClient) + 1 lần gọi top_products — vượt xa timeout mặc định 60s của queue worker
     * nên job từng bị Illuminate\Queue\TimeoutExceededException giữa chừng. 900s đủ dư cho
     * 50 * 15s + buffer xử lý/upsert DB.
     */
    public int $timeout = 900;

    public function handle(SyncOffersAction $syncOffers, SyncTopProductsAction $syncTopProducts): void
    {
        $syncOffers->handle();
        $syncTopProducts->handle();
    }
}

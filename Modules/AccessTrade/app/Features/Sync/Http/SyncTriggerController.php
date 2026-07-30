<?php

namespace Modules\AccessTrade\Features\Sync\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\AccessTrade\Jobs\SyncAccessTradeJob;

/** Nút "Đồng bộ ngay" ở dashboard/accesstrade/{offers,top-products} — đưa vào hàng đợi, không chặn request. */
class SyncTriggerController extends Controller
{
    public function store(): RedirectResponse
    {
        SyncAccessTradeJob::dispatch();

        return back()->with('success', 'Đã đưa yêu cầu đồng bộ AccessTrade vào hàng đợi — dữ liệu sẽ cập nhật trong ít phút.');
    }
}

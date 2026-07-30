<?php

namespace Modules\AccessTrade\Features\TopProductManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class TopProductAdminController extends Controller
{
    /** Dữ liệu bảng lấy qua TopProductApiController (Tabulator, remote pagination/sort/filter). */
    public function index(): View
    {
        return view('accesstrade::admin.top-products.index');
    }
}

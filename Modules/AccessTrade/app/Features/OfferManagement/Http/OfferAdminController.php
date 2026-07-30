<?php

namespace Modules\AccessTrade\Features\OfferManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class OfferAdminController extends Controller
{
    /** Dữ liệu bảng lấy qua OfferApiController (Tabulator, remote pagination/sort/filter). */
    public function index(): View
    {
        return view('accesstrade::admin.offers.index');
    }
}

<?php

namespace Modules\Heritage\Features\PublicReading\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Event\Models\Event;
use Modules\Heritage\Features\PublicReading\Queries\ListPublishedHeritageSitesHandler;
use Modules\Heritage\Features\PublicReading\Queries\ListPublishedHeritageSitesQuery;
use Modules\Heritage\Models\HeritageSite;
use Modules\Ocop\Models\OcopProduct;

/**
 * spec/Heritage_Technical_Specification.md §5.2 — trang danh sách + chi tiết 1 di tích. index()
 * nhận ?province= (link "Xem tất cả →" từ <x-province.section-heritage>) — cùng convention
 * PublicOcopController.
 */
class PublicHeritageController extends Controller
{
    public function index(Request $request, ListPublishedHeritageSitesHandler $handler): View
    {
        $sites = $handler->handle(new ListPublishedHeritageSitesQuery(
            provinceCode: $request->string('province')->value() ?: null,
            page: max(1, $request->integer('page', 1)),
        ));

        return view('heritage::public.index', compact('sites'));
    }

    /**
     * Slug không phải route key (getRouteKeyName()='uuid') — resolve thủ công, cùng lý do
     * PublicOcopController::show(). {id} trong route chỉ để phân biệt path, KHÔNG dùng để tra cứu.
     *
     * §5.2 — KHÔNG dùng $site->events()/$site->ocopProducts() (HeritageSite model không có 2
     * quan hệ này, xem HeritageSite::class docblock) — query trực tiếp Event/OcopProduct tại
     * đây, giữ Model sạch phụ thuộc module khác.
     */
    public function show(string $slug): View
    {
        $site = HeritageSite::published()->where('slug', $slug)->first();

        abort_unless($site, 404);

        $articles = $site->articles()
            ->whereHas('translations', fn ($q) => $q->published())
            ->limit(6)
            ->get();

        $events = Event::where('heritage_site_id', $site->id)
            ->published()
            ->upcoming()
            ->orderBy('start_date')
            ->limit(6)
            ->get();

        $products = OcopProduct::where('heritage_site_id', $site->id)
            ->published()
            ->orderBy('sort_order')
            ->limit(6)
            ->get();

        return view('heritage::public.show', compact('site', 'articles', 'events', 'products'));
    }
}

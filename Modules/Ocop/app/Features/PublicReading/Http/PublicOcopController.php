<?php

namespace Modules\Ocop\Features\PublicReading\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ocop\Enums\OcopProductStatus;
use Modules\Ocop\Features\PublicReading\Queries\ListPublishedOcopProductsHandler;
use Modules\Ocop\Features\PublicReading\Queries\ListPublishedOcopProductsQuery;
use Modules\Ocop\Models\OcopCategory;
use Modules\Ocop\Models\OcopProduct;

/**
 * spec/Province_Showcase_Technical_Specification.md §8 (Definition of Done #5) — trang chi tiết
 * 1 sản phẩm OCOP. index() nhận ?province= (link "Xem tất cả OCOP {tỉnh}" từ
 * <x-province.section-ocop>) — cùng convention PublicEventController.
 */
class PublicOcopController extends Controller
{
    public function index(Request $request, ListPublishedOcopProductsHandler $handler): View
    {
        $products = $handler->handle(new ListPublishedOcopProductsQuery(
            provinceCode: $request->string('province')->value() ?: null,
            categoryId: $request->integer('category_id') ?: null,
            search: $request->string('q')->trim()->value() ?: null,
            page: max(1, $request->integer('page', 1)),
        ));

        $categories = OcopCategory::active()->orderBy('name')->get(['id', 'name', 'slug']);

        return view('ocop::public.index', compact('products', 'categories'));
    }

    /** Slug không phải route key (getRouteKeyName()='uuid') — resolve thủ công, cùng lý do PublicEventController::show(). */
    public function show(string $slug): View
    {
        $product = OcopProduct::where('status', OcopProductStatus::Published)
            ->where('slug', $slug)
            ->with('category')
            ->first();

        abort_unless($product, 404);

        return view('ocop::public.show', compact('product'));
    }
}

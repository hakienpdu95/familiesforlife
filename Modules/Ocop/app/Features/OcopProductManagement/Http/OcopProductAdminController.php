<?php

namespace Modules\Ocop\Features\OcopProductManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Ocop\Enums\OcopProductStatus;
use Modules\Ocop\Features\OcopProductManagement\Actions\CreateOcopProductAction;
use Modules\Ocop\Features\OcopProductManagement\Actions\DeleteOcopProductAction;
use Modules\Ocop\Features\OcopProductManagement\Actions\StoreOcopProductImageAction;
use Modules\Ocop\Features\OcopProductManagement\Actions\UpdateOcopProductAction;
use Modules\Ocop\Features\OcopProductManagement\Data\OcopProductData;
use Modules\Ocop\Features\OcopProductManagement\Queries\ListOcopProductsForAdminHandler;
use Modules\Ocop\Features\OcopProductManagement\Queries\ListOcopProductsForAdminQuery;
use Modules\Ocop\Models\OcopCategory;
use Modules\Ocop\Models\OcopProduct;

/** spec/Province_Showcase_Technical_Specification.md §6.1 — Post-style (draft/published), không có bước duyệt. */
class OcopProductAdminController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(OcopProduct::class, 'product');
    }

    public function index(Request $request, ListOcopProductsForAdminHandler $handler): View
    {
        $products = $handler->handle(new ListOcopProductsForAdminQuery(
            search: $request->string('q')->value() ?: null,
            categoryId: $request->integer('category_id') ?: null,
            status: $request->string('status')->value() ?: null,
            page: max(1, $request->integer('page', 1)),
        ));

        $categories = OcopCategory::active()->orderBy('name')->get(['id', 'name']);

        return view('ocop::admin.products.index', compact('products', 'categories'));
    }

    public function create(): View
    {
        $categories = OcopCategory::active()->orderBy('name')->get(['id', 'name']);
        $statuses   = OcopProductStatus::cases();

        return view('ocop::admin.products.create', compact('categories', 'statuses'));
    }

    public function store(Request $request, StoreOcopProductImageAction $storeImage, CreateOcopProductAction $action): RedirectResponse
    {
        $validated = $this->validated($request);
        unset($validated['image']);
        $image = $request->hasFile('image')
            ? $storeImage->handle($request->file('image'))
            : ['image_path' => null, 'image_width' => null, 'image_height' => null, 'image_size_bytes' => null];

        $data    = OcopProductData::from([...$validated, ...$image]);
        $product = $action->handle($data);

        return redirect()->route('backend.ocop.products.index')
            ->with('success', "Đã tạo sản phẩm OCOP \"{$product->name}\".");
    }

    public function edit(OcopProduct $product): View
    {
        $categories = OcopCategory::active()->orderBy('name')->get(['id', 'name']);
        $statuses   = OcopProductStatus::cases();

        return view('ocop::admin.products.edit', compact('product', 'categories', 'statuses'));
    }

    public function update(Request $request, OcopProduct $product, StoreOcopProductImageAction $storeImage, UpdateOcopProductAction $action): RedirectResponse
    {
        $validated = $this->validated($request);
        unset($validated['image']);
        $image = $request->hasFile('image')
            ? $storeImage->handle($request->file('image'))
            : ['image_path' => null, 'image_width' => null, 'image_height' => null, 'image_size_bytes' => null];

        $data = OcopProductData::from([...$validated, ...$image]);
        $action->handle($product, $data);

        return redirect()->route('backend.ocop.products.index')
            ->with('success', 'Cập nhật sản phẩm thành công.');
    }

    public function destroy(OcopProduct $product, DeleteOcopProductAction $action): RedirectResponse
    {
        $action->handle($product);

        return redirect()->route('backend.ocop.products.index')
            ->with('success', 'Đã xoá sản phẩm.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'category_id'       => ['required', 'integer', 'exists:ocop_categories,id'],
            'name'               => ['required', 'string', 'max:150'],
            // §4.2 — chương trình OCOP quốc gia chỉ chấm từ 3 sao trở lên mới được công nhận.
            'star_rating'        => ['required', 'in:3,4,5'],
            'description'        => ['nullable', 'string'],
            'province_code'      => ['nullable', 'string', 'size:2', 'exists:provinces,province_code'],
            'ward_code'          => ['nullable', 'string', 'exists:wards,ward_code'],
            'producer_name'      => ['nullable', 'string', 'max:150'],
            'producer_address'   => ['nullable', 'string', 'max:255'],
            'image'              => ['nullable', 'image', 'max:2048'],
            'purchase_url'       => ['nullable', 'url', 'max:500'],
            'status'             => ['required', Rule::in(array_column(OcopProductStatus::cases(), 'value'))],
            'is_featured'        => ['boolean'],
            'sort_order'         => ['integer', 'min:0'],
        ]);
    }
}

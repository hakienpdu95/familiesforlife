<?php

namespace Modules\Product\Features\CatalogManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Product\Enums\ProductStatus;
use Modules\Product\Enums\ProductType;
use Modules\Product\Features\CatalogManagement\Actions\ChangeProductStatusAction;
use Modules\Product\Features\CatalogManagement\Actions\CreateProductAction;
use Modules\Product\Features\CatalogManagement\Actions\DeleteProductAction;
use Modules\Product\Features\CatalogManagement\Actions\UpdateProductAction;
use Modules\Product\Features\CatalogManagement\Data\ProductData;
use Modules\Product\Features\CatalogManagement\Exceptions\ProductStillReferencedException;
use Modules\Product\Features\CatalogManagement\Queries\ListProductsForAdminHandler;
use Modules\Product\Features\CatalogManagement\Queries\ListProductsForAdminQuery;
use Modules\Product\Features\CategoryManagement\Queries\ListCategoriesForAdminHandler;
use Modules\Product\Features\CategoryManagement\Queries\ListCategoriesForAdminQuery;
use Modules\Product\Models\Product;

class ProductAdminController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Product::class, 'product');
    }

    public function index(Request $request, ListProductsForAdminHandler $handler, ListCategoriesForAdminHandler $categoryHandler): View
    {
        $products = $handler->handle(new ListProductsForAdminQuery(
            page:       max(1, $request->integer('page', 1)),
            search:     $request->string('q')->value() ?: null,
            categoryId: $request->integer('category_id') ?: null,
            status:     $request->string('status')->value() ?: null,
            type:       $request->string('type')->value() ?: null,
        ));

        $categories = $categoryHandler->handle(new ListCategoriesForAdminQuery());

        return view('product::admin.products.index', compact('products', 'categories'));
    }

    public function create(ListCategoriesForAdminHandler $categoryHandler): View
    {
        $categories = $categoryHandler->handle(new ListCategoriesForAdminQuery());

        return view('product::admin.products.create', compact('categories'));
    }

    public function store(Request $request, CreateProductAction $action): RedirectResponse
    {
        $data    = ProductData::from($this->validated($request));
        $product = $action->handle($data);

        return redirect()->route('backend.products.index')
            ->with('success', "Sản phẩm \"{$product->name}\" đã được tạo.");
    }

    public function edit(Product $product, ListCategoriesForAdminHandler $categoryHandler): View
    {
        $categories = $categoryHandler->handle(new ListCategoriesForAdminQuery());

        return view('product::admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product, UpdateProductAction $action): RedirectResponse
    {
        $data = ProductData::from($this->validated($request, $product->id));
        $action->handle($product, $data);

        return redirect()->route('backend.products.index')
            ->with('success', 'Cập nhật sản phẩm thành công.');
    }

    public function destroy(Product $product, DeleteProductAction $action): RedirectResponse
    {
        try {
            $action->handle($product);
        } catch (ProductStillReferencedException $e) {
            return back()->withErrors(['product' => $e->getMessage()]);
        }

        return redirect()->route('backend.products.index')
            ->with('success', "Đã xoá sản phẩm \"{$product->name}\".");
    }

    public function changeStatus(Request $request, Product $product, ChangeProductStatusAction $action): RedirectResponse
    {
        $this->authorize('update', $product);

        $request->validate(['status' => ['required', 'in:' . implode(',', array_column(ProductStatus::cases(), 'value'))]]);

        $action->handle($product, ProductStatus::from($request->string('status')->value()));

        return back()->with('success', 'Đã cập nhật trạng thái sản phẩm.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $urlRule = ['nullable', 'url', 'regex:/^https?:\/\//i', 'max:500'];

        return $request->validate([
            'category_id'            => ['nullable', 'integer', 'exists:product_categories,id'],
            'name'                   => ['required', 'string', 'max:250'],
            'type'                   => ['required', 'in:' . implode(',', array_column(ProductType::cases(), 'value'))],
            'status'                 => ['required', 'in:' . implode(',', array_column(ProductStatus::cases(), 'value'))],
            'sku'                    => ['nullable', 'string', 'max:60'],
            'short_description'      => ['nullable', 'string', 'max:300'],
            'description'            => ['nullable', 'string'],
            'price'                  => ['nullable', 'numeric', 'min:0'],
            'price_label'            => ['nullable', 'string', 'max:100'],
            'currency'               => ['nullable', 'string', 'size:3'],
            'cover_image_url'        => ['nullable', 'string', 'max:500'],
            'shopee_url'             => $urlRule,
            'tiktok_url'             => $urlRule,
            'supplier_url'           => $urlRule,
            'supplier_homepage_url'  => $urlRule,
            'is_featured'            => ['boolean'],
            'sort_order'             => ['integer', 'min:0'],
        ]);
    }
}

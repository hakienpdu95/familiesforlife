<?php

namespace Modules\OcopRubric\Features\ProductRegistry\Http;

use App\Enums\PermissionEnum as P;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\OcopRubric\Features\ProductGroupCatalog\Queries\ListProductGroupsHandler;
use Modules\OcopRubric\Features\ProductGroupCatalog\Queries\ListProductGroupsQuery;
use Modules\OcopRubric\Features\ProductRegistry\Actions\ArchiveProductAction;
use Modules\OcopRubric\Features\ProductRegistry\Actions\RegisterProductAction;
use Modules\OcopRubric\Features\ProductRegistry\Actions\UpdateProductAction;
use Modules\OcopRubric\Features\ProductRegistry\Data\ProductData;
use Modules\OcopRubric\Features\ProductRegistry\Queries\GetProductHandler;
use Modules\OcopRubric\Features\ProductRegistry\Queries\GetProductQuery;
use Modules\OcopRubric\Features\ProductRegistry\Queries\ListProductsHandler;
use Modules\OcopRubric\Features\ProductRegistry\Queries\ListProductsQuery;
use Modules\OcopRubric\Models\OcopProduct;

class ProductController extends Controller
{
    public function __construct(private readonly ListProductGroupsHandler $groupsHandler) {}

    public function index(Request $request, ListProductsHandler $handler): View
    {
        $products = $handler->handle(new ListProductsQuery(
            status:         $request->string('status')->value() ?: null,
            productGroupId: $request->integer('product_group_id') ?: null,
            search:         $request->string('q')->value() ?: null,
        ));

        $groups = $this->groupsHandler->handle(new ListProductGroupsQuery(activeOnly: true));

        return view('ocoprubric::products.index', compact('products', 'groups'));
    }

    public function create(): View
    {
        $groups = $this->groupsHandler->handle(new ListProductGroupsQuery(activeOnly: true));

        return view('ocoprubric::products.create', compact('groups'));
    }

    public function store(Request $request, RegisterProductAction $action): RedirectResponse
    {
        $this->authorizeManage();

        $data = ProductData::from($this->validated($request));
        $product = $action->handle($data);

        return redirect()->route('ocop.products.show', $product)
            ->with('success', "Đã đăng ký sản phẩm \"{$product->name}\".");
    }

    public function show(OcopProduct $product, GetProductHandler $handler): View
    {
        $product = $handler->handle(new GetProductQuery($product->id));

        return view('ocoprubric::products.show', compact('product'));
    }

    public function edit(OcopProduct $product): View
    {
        $groups = $this->groupsHandler->handle(new ListProductGroupsQuery(activeOnly: true));

        return view('ocoprubric::products.edit', compact('product', 'groups'));
    }

    public function update(Request $request, OcopProduct $product, UpdateProductAction $action): RedirectResponse
    {
        $this->authorizeManage();

        $data = ProductData::from($this->validated($request));
        $action->handle($product, $data);

        return redirect()->route('ocop.products.show', $product)->with('success', 'Cập nhật sản phẩm thành công.');
    }

    public function archive(OcopProduct $product, ArchiveProductAction $action): RedirectResponse
    {
        $this->authorizeManage();

        $action->handle($product);

        return back()->with('success', "Đã lưu trữ sản phẩm \"{$product->name}\".");
    }

    public function destroy(OcopProduct $product): RedirectResponse
    {
        $this->authorizeManage();

        $product->delete();

        return redirect()->route('ocop.products.index')->with('success', "Đã xóa sản phẩm \"{$product->name}\".");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'product_group_id' => 'required|integer|exists:ocop_product_groups,id',
            'name'             => 'required|string|max:255',
            'product_code'     => 'nullable|string|max:60',
        ]);
    }

    /** Route group chỉ gate ở mức OCOP_PRODUCT_VIEW — MANAGE kiểm tra riêng cho action ghi. */
    private function authorizeManage(): void
    {
        abort_unless(auth()->user()?->can(P::OCOP_PRODUCT_MANAGE->value), 403);
    }
}

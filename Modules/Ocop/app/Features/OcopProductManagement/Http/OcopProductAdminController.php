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
        // Cây danh mục OCOP chính thức (spec/danhmuc.html) dạng phẳng kèm depth — <select> hiển
        // thị thụt lề đúng cấp bậc I → Nhóm → Phân nhóm, thay vì liệt kê phẳng theo tên.
        $categoryTree = OcopCategory::flatTree();
        $statuses     = OcopProductStatus::cases();

        return view('ocop::admin.products.create', compact('categoryTree', 'statuses'));
    }

    public function store(Request $request, CreateOcopProductAction $action): RedirectResponse
    {
        $data    = OcopProductData::from($this->validated($request));
        $product = $action->handle($data);

        return redirect()->route('backend.ocop.products.index')
            ->with('success', "Đã tạo sản phẩm OCOP \"{$product->name}\".");
    }

    public function edit(OcopProduct $product): View
    {
        // Cùng lý do create() ở trên.
        $categoryTree = OcopCategory::flatTree();
        $statuses     = OcopProductStatus::cases();

        return view('ocop::admin.products.edit', compact('product', 'categoryTree', 'statuses'));
    }

    public function update(Request $request, OcopProduct $product, UpdateOcopProductAction $action): RedirectResponse
    {
        $data = OcopProductData::from($this->validated($request));
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
            // spec/Media_Library_Technical_Specification.md §8 — chỉ dùng ở create form.
            'cover_media_uuid'   => ['nullable', 'string'],
            'purchase_url'       => ['nullable', 'url', 'max:500'],
            'status'             => ['required', Rule::in(array_column(OcopProductStatus::cases(), 'value'))],
            'is_featured'        => ['boolean'],
            'sort_order'         => ['integer', 'min:0'],
        ], [
            'category_id.required'     => 'Vui lòng chọn danh mục.',
            'category_id.exists'       => 'Danh mục được chọn không hợp lệ.',
            'name.required'            => 'Vui lòng nhập tên sản phẩm.',
            'name.max'                 => 'Tên sản phẩm không được vượt quá :max ký tự.',
            'star_rating.required'     => 'Vui lòng chọn hạng sao.',
            'star_rating.in'           => 'Hạng sao không hợp lệ — chỉ chấp nhận 3, 4 hoặc 5 sao.',
            'province_code.exists'     => 'Tỉnh/thành được chọn không hợp lệ.',
            'ward_code.exists'         => 'Phường/xã được chọn không hợp lệ.',
            'producer_name.max'       => 'Tên nhà sản xuất không được vượt quá :max ký tự.',
            'producer_address.max'    => 'Địa chỉ nhà sản xuất không được vượt quá :max ký tự.',
            'purchase_url.url'         => 'URL không hợp lệ — phải bắt đầu bằng https://',
            'purchase_url.max'        => 'URL không được vượt quá :max ký tự.',
            'status.required'          => 'Vui lòng chọn trạng thái.',
            'status.in'                => 'Trạng thái không hợp lệ.',
            'sort_order.integer'      => 'Thứ tự hiển thị phải là số nguyên.',
            'sort_order.min'          => 'Thứ tự hiển thị không được âm.',
        ]);
    }
}

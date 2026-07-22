<?php

namespace Modules\Product\Features\CatalogManagement\Http;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Approval\Actions\ApproveAction;
use Modules\Approval\Actions\ArchiveAction;
use Modules\Approval\Actions\PublishAction;
use Modules\Approval\Actions\RejectAction;
use Modules\Approval\Actions\SubmitForApprovalAction;
use Modules\Approval\Exceptions\InvalidTransitionException;
use Modules\Product\Enums\ProductStatus;
use Modules\Product\Enums\ProductType;
use Modules\Product\Features\CatalogManagement\Actions\ChangeProductStatusAction;
use Modules\Product\Features\CatalogManagement\Actions\CreateProductAction;
use Modules\Product\Features\CatalogManagement\Actions\DeleteProductAction;
use Modules\Product\Features\CatalogManagement\Actions\UpdateProductAction;
use Modules\Product\Features\CatalogManagement\Data\ProductData;
use Modules\Product\Features\CatalogManagement\Exceptions\ProductStillReferencedException;
use Modules\Product\Features\CategoryManagement\Queries\ListCategoriesForAdminHandler;
use Modules\Product\Features\CategoryManagement\Queries\ListCategoriesForAdminQuery;
use Modules\Product\Models\Product;

class ProductAdminController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Product::class, 'product');
    }

    /** Dữ liệu bảng lấy qua ProductApiController (Tabulator, remote pagination/sort/filter) — chỉ truyền categories cho dropdown lọc. */
    public function index(ListCategoriesForAdminHandler $categoryHandler): View
    {
        $categories = $categoryHandler->handle(new ListCategoriesForAdminQuery());

        return view('product::admin.products.index', compact('categories'));
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

    public function edit(Product $product, ListCategoriesForAdminHandler $categoryHandler): string
    {
        $categories = $categoryHandler->handle(new ListCategoriesForAdminQuery());

        // platform_content_moderator (organization_id=null) không có TenantContext trỏ tới tổ chức của
        // $product — nếu không bọc, $product->approvalSubject (OrganizationScope, §7.1) resolve
        // null khi render Blade, ẩn mất toàn bộ badge/nút "Duyệt nội dung" dù moderator vẫn xem
        // được trang (Product route binding không tenant-scope). Bug thật phát hiện khi kiểm thử
        // dashboard "Xem & duyệt" bằng tài khoản platform_content_moderator — cùng nguyên nhân với
        // runApprovalTransition() ở dưới, chỉ khác là ở đây cần bọc luôn cho GET, không chỉ POST.
        // PHẢI gọi ->render() NGAY TRONG closure — view() chỉ build đối tượng View (lazy), Blade
        // thật sự chạy khi Laravel gọi render() sau khi controller return; nếu trả thẳng View
        // chưa render, finally của runForOrganization() đã phục hồi TenantContext CŨ trước khi
        // Blade kịp đọc $product->approvalSubject, khiến bug vẫn y nguyên dù có bọc.
        // Dùng mảng literal, KHÔNG compact() — fn() chỉ auto-capture biến được tham chiếu trực
        // tiếp bằng tên; compact('product') chỉ truyền chuỗi nên PHP không tự bắt được.
        return TenantContext::runForOrganization(
            $product->organization,
            fn () => view('product::admin.products.edit', ['product' => $product, 'categories' => $categories])->render(),
        );
    }

    public function update(Request $request, Product $product, UpdateProductAction $action): RedirectResponse
    {
        // Product không có route "show" riêng nên edit()/update() dùng CHUNG ability `update`
        // qua authorizeResource() — platform_viewer được nới quyền `update` chỉ để XEM được
        // trang edit (giám sát dashboard), KHÔNG được phép submit thay đổi thật (§3.3, role
        // read-only). Chặn tường minh ở đây thay vì tách riêng ability view/update cho Product
        // (đổi route convention hiện có, tốn công hơn cần thiết cho 1 role).
        abort_if($request->user()?->isPlatformViewer(), 403);

        $data = ProductData::from($this->validated($request, $product->id));

        try {
            $action->handle($product, $data);
        } catch (InvalidTransitionException $e) {
            // Sản phẩm đã Archived mà sửa trúng 1 trường nội dung (name/short_description/
            // description/cover_image_url) → HasApproval::bootHasApproval() tự gọi
            // ReviseContentAction, action này chặn vì Archived là read-only ở tầng nội dung
            // (spec/Workflow_Approval_Technical_Specification.md §8.4). ProductPolicy::update()
            // CỐ Ý không chặn toàn bộ form vì các trường vận hành (giá, trạng thái kinh
            // doanh…) vẫn sửa được bình thường dù nội dung đã lưu trữ (§2.3) — lỗi chỉ xảy ra
            // đúng lúc đụng vào trường nội dung, nên xử lý ở đây thay vì chặn cả form.
            return back()->withInput()->with('error', 'Không thể sửa nội dung sản phẩm đã lưu trữ. Các trường khác (giá, trạng thái kinh doanh…) đã được lưu — chỉ phần nội dung bị bỏ qua.');
        }

        return redirect()->route('backend.products.index')
            ->with('success', 'Cập nhật sản phẩm thành công.');
    }

    public function destroy(Request $request, Product $product, DeleteProductAction $action): RedirectResponse|JsonResponse
    {
        $name = $product->name;

        try {
            $action->handle($product);
        } catch (ProductStillReferencedException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['product' => $e->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => "Đã xoá sản phẩm \"{$name}\"."]);
        }

        return redirect()->route('backend.products.index')
            ->with('success', "Đã xoá sản phẩm \"{$name}\".");
    }

    public function changeStatus(Request $request, Product $product, ChangeProductStatusAction $action): RedirectResponse
    {
        $this->authorize('update', $product);

        $request->validate(['status' => ['required', 'in:' . implode(',', array_column(ProductStatus::cases(), 'value'))]]);

        $action->handle($product, ProductStatus::from($request->string('status')->value()));

        return back()->with('success', 'Đã cập nhật trạng thái sản phẩm.');
    }

    // ── Approval workflow — Platform Approval Gateway (Hà Kiên nội bộ) ─────────────────
    // Trạng thái duyệt nội dung — độc lập với changeStatus() ở trên (trục vận hành/kinh
    // doanh, §2.3). Action không tự check quyền, controller tự authorize trước khi gọi.
    // approve/reject/publishApproval/archiveApproval do platform_content_moderator xử lý — tài khoản
    // này KHÔNG thuộc tổ chức nào (organization_id=null) nên TenantContext của chính họ không
    // trỏ tới tổ chức của $product; runApprovalTransition() bọc lệnh gọi Action trong
    // TenantContext::runForOrganization($product->organization, ...) để các query nội bộ
    // (ApprovalSubject, ApprovalLog — đều có OrganizationScope) resolve đúng tổ chức của
    // CHÍNH sản phẩm đang xử lý, không phải tổ chức (nếu có) của người đang thao tác. Áp dụng
    // luôn cho submitApproval (thao tác của chính doanh nghiệp) — vô hại vì khi đó
    // TenantContext đã đúng sẵn tổ chức của họ, set lại cùng giá trị không đổi gì.

    public function submitApproval(Product $product, SubmitForApprovalAction $action): RedirectResponse
    {
        $this->authorize('submitForApproval', $product);

        return $this->runApprovalTransition($product, fn () => $action->handle($product), 'Đã gửi sản phẩm để chờ duyệt.');
    }

    public function approveContent(Product $product, ApproveAction $action): RedirectResponse
    {
        $this->authorize('approve', $product);

        return $this->runApprovalTransition($product, fn () => $action->handle($product), 'Đã duyệt nội dung sản phẩm.');
    }

    public function rejectContent(Request $request, Product $product, RejectAction $action): RedirectResponse
    {
        $this->authorize('reject', $product);

        $reason = $request->validate(['reason' => ['required', 'string', 'min:10']])['reason'];

        return $this->runApprovalTransition($product, fn () => $action->handle($product, $reason), 'Đã từ chối duyệt.');
    }

    public function publishContent(Product $product, PublishAction $action): RedirectResponse
    {
        $this->authorize('publishApproval', $product);

        return $this->runApprovalTransition($product, fn () => $action->handle($product), 'Đã xuất bản nội dung sản phẩm.');
    }

    public function archiveContent(Product $product, ArchiveAction $action): RedirectResponse
    {
        $this->authorize('archiveApproval', $product);

        return $this->runApprovalTransition($product, fn () => $action->handle($product), 'Đã lưu trữ sản phẩm.');
    }

    private function runApprovalTransition(Product $product, \Closure $callback, string $successMessage): RedirectResponse
    {
        try {
            TenantContext::runForOrganization($product->organization, $callback);
        } catch (InvalidTransitionException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $successMessage);
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

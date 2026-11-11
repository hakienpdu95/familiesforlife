<?php

namespace Modules\Product\Features\CatalogManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Approval\Actions\ApproveAction;
use Modules\Approval\Actions\ArchiveAction;
use Modules\Approval\Actions\RejectAction;
use Modules\Approval\Actions\SubmitForApprovalAction;
use Modules\Approval\Enums\ApprovalStatus;
use Modules\Approval\Exceptions\InvalidTransitionException;
use Modules\Product\Enums\ProductStatus;
use Modules\Product\Enums\ProductType;
use Modules\Product\Features\CatalogManagement\Actions\ChangeProductStatusAction;
use Modules\Product\Features\CatalogManagement\Actions\CreateProductAction;
use Modules\Product\Features\CatalogManagement\Actions\DeleteProductAction;
use Modules\Product\Features\CatalogManagement\Actions\FastTrackPublishProductAction;
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
        $published = $this->fastTrackIfFinalApprover($request, $product);

        // "Tạo & thêm tiếp" — nhập nhiều sản phẩm liên tục, quay lại form trống thay vì danh sách.
        $route = $request->input('after_save') === 'new' ? 'backend.products.create' : 'backend.products.index';

        return redirect()->route($route)
            ->with('success', $published
                ? __('Sản phẩm ":name" đã được tạo và xuất bản.', ['name' => $product->name])
                : __('Sản phẩm ":name" đã được tạo và gửi duyệt.', ['name' => $product->name]));
    }

    public function edit(Product $product, ListCategoriesForAdminHandler $categoryHandler): View
    {
        $categories = $categoryHandler->handle(new ListCategoriesForAdminQuery());

        return view('product::admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product, UpdateProductAction $action): RedirectResponse
    {
        // Product không có route "show" riêng nên edit()/update() dùng CHUNG ability `update`
        // qua authorizeResource() — platform_viewer được nới quyền `update` chỉ để XEM được
        // trang edit (giám sát dashboard), KHÔNG được phép submit thay đổi thật (§3.3, role
        // read-only). Chặn tường minh ở đây thay vì tách riêng ability view/update cho Product
        // (đổi route convention hiện có, tốn công hơn cần thiết cho 1 role).
        abort_if($request->user()?->isPlatformViewer(), 403);

        $validated = $this->validated($request, $product->id);
        $kept = $product->getMedia(Product::GALLERY)->whereNotIn('uuid', $validated['remove_media_uuids'] ?? [])->count();
        if ($kept + count($validated['media_uuids'] ?? []) > Product::MAX_IMAGES) {
            throw \Illuminate\Validation\ValidationException::withMessages(['media_uuids' => __('Tối đa :max ảnh cho mỗi sản phẩm (hiện còn :kept ảnh).', ['max' => Product::MAX_IMAGES, 'kept' => $kept])]);
        }
        $data = ProductData::from([...$validated, 'cover_image_url' => $product->cover_image_url]);

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
            return back()->withInput()->with('error', __('Không thể sửa nội dung sản phẩm đã lưu trữ. Các trường khác (giá, trạng thái kinh doanh…) đã được lưu — chỉ phần nội dung bị bỏ qua.'));
        }

        $published = $this->fastTrackIfFinalApprover($request, $product->refresh());

        return redirect()->route('backend.products.index')
            ->with('success', $published ? __('Đã cập nhật và xuất bản sản phẩm.') : __('Cập nhật sản phẩm thành công.'));
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
            return response()->json(['message' => __('Đã xoá sản phẩm ":name".', ['name' => $name])]);
        }

        return redirect()->route('backend.products.index')
            ->with('success', __('Đã xoá sản phẩm ":name".', ['name' => $name]));
    }

    public function changeStatus(Request $request, Product $product, ChangeProductStatusAction $action): RedirectResponse
    {
        $this->authorize('update', $product);

        $request->validate(['status' => ['required', 'in:' . implode(',', array_column(ProductStatus::cases(), 'value'))]]);

        $action->handle($product, ProductStatus::from($request->string('status')->value()));

        return back()->with('success', __('Đã cập nhật trạng thái sản phẩm.'));
    }

    // ── Approval workflow — Platform Approval Gateway (Hà Kiên nội bộ) ─────────────────
    // Trạng thái duyệt nội dung — độc lập với changeStatus() ở trên (trục vận hành/kinh
    // doanh, §2.3). Action không tự check quyền, controller tự authorize trước khi gọi.
    // approve/reject/publishApproval/archiveApproval do platform_content_moderator xử lý.

    public function submitApproval(Product $product, SubmitForApprovalAction $action): RedirectResponse
    {
        $this->authorize('submitForApproval', $product);

        if (Gate::allows('publishApproval', $product)) {
            return back()->with('error', __('Tài khoản của bạn có quyền xuất bản trực tiếp, không cần gửi duyệt.'));
        }

        return $this->runApprovalTransition($product, fn () => $action->handle($product), __('Đã gửi sản phẩm để chờ duyệt.'));
    }

    public function approveContent(Product $product, ApproveAction $action): RedirectResponse
    {
        $this->authorize('approve', $product);

        return $this->runApprovalTransition($product, fn () => $action->handle($product), __('Đã duyệt nội dung sản phẩm.'));
    }

    public function rejectContent(Request $request, Product $product, RejectAction $action): RedirectResponse
    {
        $this->authorize('reject', $product);

        $reason = $request->validate(['reason' => ['required', 'string', 'min:10']])['reason'];

        return $this->runApprovalTransition($product, fn () => $action->handle($product, $reason), __('Đã từ chối duyệt.'));
    }

    public function publishContent(Product $product, FastTrackPublishProductAction $action): RedirectResponse
    {
        $this->authorize('publishApproval', $product);

        return $this->runApprovalTransition($product, fn () => $action->handle($product), __('Đã duyệt và xuất bản nội dung sản phẩm.'));
    }

    private function fastTrackIfFinalApprover(Request $request, Product $product): bool
    {
        $product->load('approvalSubject');

        if (! $request->user()->can('publishApproval', $product) || ! in_array($product->approvalStatus(), [ApprovalStatus::Draft, ApprovalStatus::Pending, ApprovalStatus::Approved], true)) {
            return false;
        }

        app(FastTrackPublishProductAction::class)->handle($product);

        return true;
    }

    public function archiveContent(Product $product, ArchiveAction $action): RedirectResponse
    {
        $this->authorize('archiveApproval', $product);

        return $this->runApprovalTransition($product, fn () => $action->handle($product), __('Đã lưu trữ sản phẩm.'));
    }

    private function runApprovalTransition(Product $product, \Closure $callback, string $successMessage): RedirectResponse
    {
        try {
            $callback();
        } catch (InvalidTransitionException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $successMessage);
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $urlRule = ['nullable', 'url', 'regex:/^https?:\/\//i', 'max:500'];

        if (is_string($request->input('media_uuids'))) {
            $request->merge(['media_uuids' => json_decode($request->input('media_uuids'), true) ?: []]);
        }

        foreach (['media_uuids', 'remove_media_uuids'] as $field) {
            if (! is_array($request->input($field))) {
                $request->merge([$field => []]);
            }
        }

        return $request->validate([
            'category_id'            => ['nullable', 'integer', 'exists:product_categories,id'],
            'name'                   => ['required', 'string', 'max:250'],
            'type'                   => ['required', 'in:' . implode(',', array_column(ProductType::cases(), 'value'))],
            'status'                 => ['required', 'in:' . implode(',', array_column(ProductStatus::cases(), 'value'))],
            'sku'                    => ['nullable', 'string', 'max:60'],
            'short_description'      => ['nullable', 'string', 'max:300'],
            'description'            => ['nullable', 'string', 'max:20000'],
            'content'                => ['nullable', 'string', 'max:65000'],
            'price'                  => ['nullable', 'numeric', 'min:0'],
            'price_label'            => ['nullable', 'string', 'max:100'],
            'currency'               => ['nullable', 'string', 'size:3'],
            'media_uuids'            => ['nullable', 'array', 'max:'.Product::MAX_IMAGES],
            'media_uuids.*'          => ['string', 'uuid'],
            'remove_media_uuids'     => ['nullable', 'array'],
            'remove_media_uuids.*'   => ['string', 'uuid'],
            'remove_cover'           => ['boolean'],
            'shopee_url'             => $urlRule,
            'tiktok_url'             => $urlRule,
            'supplier_url'           => $urlRule,
            'supplier_homepage_url'  => $urlRule,
            'is_featured'            => ['boolean'],
            'sort_order'             => ['integer', 'min:0'],
        ], [
            'category_id.exists'           => __('Danh mục được chọn không hợp lệ.'),
            'name.required'                => __('Vui lòng nhập tên sản phẩm.'),
            'name.max'                     => __('Tên sản phẩm không được vượt quá :max ký tự.'),
            'type.required'                => __('Vui lòng chọn loại sản phẩm.'),
            'type.in'                      => __('Loại sản phẩm không hợp lệ.'),
            'status.required'              => __('Vui lòng chọn trạng thái.'),
            'status.in'                    => __('Trạng thái không hợp lệ.'),
            'sku.max'                      => __('SKU không được vượt quá :max ký tự.'),
            'short_description.max'        => __('Mô tả ngắn không được vượt quá :max ký tự.'),
            'content.string'               => __('Nội dung không hợp lệ.'),
            'content.max'                  => __('Nội dung quá dài (tối đa :max ký tự, tính cả mã HTML).'),
            'price.numeric'                => __('Giá phải là số.'),
            'price.min'                    => __('Giá không được âm.'),
            'price_label.max'              => __('Nhãn giá không được vượt quá :max ký tự.'),
            'currency.size'                => __('Tiền tệ phải là mã 3 ký tự (VD: VND, USD).'),
            'media_uuids.max'              => __('Tối đa :max ảnh cho mỗi sản phẩm.'),
            'media_uuids.*.uuid'           => __('Ảnh tải lên không hợp lệ — vui lòng tải lại.'),
            'remove_cover.boolean'         => __('Giá trị "Xóa ảnh hiện tại" không hợp lệ.'),
            'shopee_url.url'               => __('Link Shopee không đúng định dạng URL.'),
            'shopee_url.regex'             => __('Link Shopee phải bắt đầu bằng http:// hoặc https://.'),
            'shopee_url.max'               => __('Link Shopee không được vượt quá :max ký tự.'),
            'tiktok_url.url'               => __('Link TikTok Shop không đúng định dạng URL.'),
            'tiktok_url.regex'             => __('Link TikTok Shop phải bắt đầu bằng http:// hoặc https://.'),
            'tiktok_url.max'               => __('Link TikTok Shop không được vượt quá :max ký tự.'),
            'supplier_url.url'             => __('Link sản phẩm tại NCC không đúng định dạng URL.'),
            'supplier_url.regex'           => __('Link sản phẩm tại NCC phải bắt đầu bằng http:// hoặc https://.'),
            'supplier_url.max'             => __('Link sản phẩm tại NCC không được vượt quá :max ký tự.'),
            'supplier_homepage_url.url'    => __('Website nhà cung cấp không đúng định dạng URL.'),
            'supplier_homepage_url.regex'  => __('Website nhà cung cấp phải bắt đầu bằng http:// hoặc https://.'),
            'supplier_homepage_url.max'    => __('Website nhà cung cấp không được vượt quá :max ký tự.'),
            'is_featured.boolean'          => __('Giá trị "Sản phẩm nổi bật" không hợp lệ.'),
            'sort_order.integer'           => __('Thứ tự hiển thị phải là số nguyên.'),
            'sort_order.min'               => __('Thứ tự hiển thị không được âm.'),
        ]);
    }
}

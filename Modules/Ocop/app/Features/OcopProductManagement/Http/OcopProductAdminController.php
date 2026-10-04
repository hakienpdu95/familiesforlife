<?php

namespace Modules\Ocop\Features\OcopProductManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Modules\Heritage\Models\HeritageSite;
use Modules\Ocop\Enums\OcopProductStatus;
use Modules\Ocop\Features\OcopProductManagement\Actions\CreateOcopProductAction;
use Modules\Ocop\Features\OcopProductManagement\Actions\DeleteOcopProductAction;
use Modules\Ocop\Features\OcopProductManagement\Actions\StoreOcopProductDocumentsAction;
use Modules\Ocop\Features\OcopProductManagement\Actions\UpdateOcopProductAction;
use Modules\Ocop\Features\OcopProductManagement\Data\OcopProductData;
use Modules\Ocop\Models\OcopCategory;
use Modules\Ocop\Models\OcopProduct;
use Modules\OcopSubject\Features\OcopSubjectManagement\Queries\ListOcopSubjectsForPickerHandler;
use Modules\OcopSubject\Features\OcopSubjectManagement\Queries\ListOcopSubjectsForPickerQuery;

/** spec/Province_Showcase_Technical_Specification.md §6.1 — Post-style (draft/published), không có bước duyệt. */
class OcopProductAdminController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(OcopProduct::class, 'product');
    }

    /** Dữ liệu bảng lấy qua OcopProductApiController (Tabulator, remote pagination/sort/filter). */
    public function index(): View
    {
        $categories = OcopCategory::active()->orderBy('name')->get(['id', 'name']);

        return view('ocop::admin.products.index', compact('categories'));
    }

    public function create(Request $request, ListOcopSubjectsForPickerHandler $ocopSubjectPicker): View
    {
        // Cây danh mục OCOP chính thức (spec/danhmuc.html) dạng phẳng kèm depth — <select> hiển
        // thị thụt lề đúng cấp bậc I → Nhóm → Phân nhóm, thay vì liệt kê phẳng theo tên.
        $categoryTree = OcopCategory::flatTree();
        $statuses = OcopProductStatus::cases();
        $heritageSites = $this->heritageSitesForPicker();
        $ocopSubjects = $ocopSubjectPicker->handle(new ListOcopSubjectsForPickerQuery);

        $selectedSubjectId = $request->integer('subject_id');
        $selectedSubjectId = $ocopSubjects->contains('id', $selectedSubjectId) ? $selectedSubjectId : null;

        return view('ocop::admin.products.create', compact('categoryTree', 'statuses', 'heritageSites', 'ocopSubjects', 'selectedSubjectId'));
    }

    public function store(Request $request, CreateOcopProductAction $action, StoreOcopProductDocumentsAction $storeDocuments): RedirectResponse
    {
        $validated = $this->validated($request);
        $data = OcopProductData::from(Arr::except($validated, 'documents'));
        $product = $action->handle($data);
        $storeDocuments->handle($product, $validated['documents'] ?? []);

        return redirect()->route('backend.ocop.products.index')
            ->with('success', "Đã tạo sản phẩm OCOP \"{$product->name}\".");
    }

    public function edit(OcopProduct $product, ListOcopSubjectsForPickerHandler $ocopSubjectPicker): View
    {
        // Cùng lý do create() ở trên.
        $categoryTree = OcopCategory::flatTree();
        $statuses = OcopProductStatus::cases();
        $heritageSites = $this->heritageSitesForPicker();
        $ocopSubjects = $ocopSubjectPicker->handle(new ListOcopSubjectsForPickerQuery($product->ocop_subject_id));

        return view('ocop::admin.products.edit', compact('product', 'categoryTree', 'statuses', 'heritageSites', 'ocopSubjects'));
    }

    public function update(Request $request, OcopProduct $product, UpdateOcopProductAction $action, StoreOcopProductDocumentsAction $storeDocuments): RedirectResponse
    {
        $validated = $this->validated($request);
        $this->ensureDocumentLimit($product, $validated);
        $data = OcopProductData::from(Arr::except($validated, 'documents'));
        $action->handle($product, $data);
        $storeDocuments->handle($product, $validated['documents'] ?? []);

        return redirect()->route('backend.ocop.products.index')
            ->with('success', 'Cập nhật sản phẩm thành công.');
    }

    public function destroy(Request $request, OcopProduct $product, DeleteOcopProductAction $action): RedirectResponse|JsonResponse
    {
        $action->handle($product);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xoá sản phẩm.']);
        }

        return redirect()->route('backend.ocop.products.index')
            ->with('success', 'Đã xoá sản phẩm.');
    }

    /**
     * spec/Heritage_Technical_Specification.md §8.2 — CHỈ hiện HeritageSite heritage_type ∈
     * {intangible, historical_monument} làm GỢI Ý MẶC ĐỊNH (ưu tiên đầu danh sách), KHÔNG ép
     * cứng — editor vẫn chọn được loại khác nếu hợp lý (VD sản phẩm ẩm thực gắn 1 danh lam
     * thắng cảnh có truyền thống ẩm thực riêng), nên danh sách vẫn gồm mọi di tích published.
     */
    private function heritageSitesForPicker(): Collection
    {
        return HeritageSite::published()
            ->orderByRaw("CASE WHEN heritage_type IN ('intangible', 'historical_monument') THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get(['id', 'name', 'heritage_type', 'province_name']);
    }

    private function ensureDocumentLimit(OcopProduct $product, array $validated): void
    {
        $removed = $validated['remove_media_uuids'] ?? [];

        foreach ($validated['documents'] ?? [] as $collection => $files) {
            $kept = $product->getMedia($collection)->whereNotIn('uuid', $removed)->count();

            if ($kept + count($files) > OcopProduct::MAX_DOCUMENTS) {
                throw ValidationException::withMessages([
                    "documents.$collection" => 'Tối đa '.OcopProduct::MAX_DOCUMENTS." tệp cho mỗi loại hồ sơ (hiện có {$kept} tệp).",
                ]);
            }
        }
    }

    private function validated(Request $request): array
    {
        if (is_string($request->input('media_uuids'))) {
            $request->merge(['media_uuids' => json_decode($request->input('media_uuids'), true) ?: []]);
        }

        return $request->validate([
            'category_id' => ['required', 'integer', 'exists:ocop_categories,id'],
            'name' => ['required', 'string', 'max:150'],
            // §4.2 — chương trình OCOP quốc gia chỉ chấm từ 3 sao trở lên mới được công nhận.
            'star_rating' => ['required', 'in:3,4,5'],
            'description' => ['nullable', 'string'],
            'story' => ['nullable', 'string', 'max:65000'],
            'origin' => ['nullable', 'string', 'max:255'],
            'production_date' => ['nullable', 'string', 'max:100'],
            'shelf_life' => ['nullable', 'string', 'max:100'],
            'ingredients' => ['nullable', 'string', 'max:5000'],
            'usage_instructions' => ['nullable', 'string', 'max:5000'],
            'storage_instructions' => ['nullable', 'string', 'max:5000'],
            'ocop_subject_id' => ['required', 'integer', Rule::exists('ocop_subjects', 'id')->whereNull('deleted_at')],
            // spec/Heritage_Technical_Specification.md §8.2 — tuỳ chọn.
            'heritage_site_id' => ['nullable', 'integer', 'exists:heritage_sites,id'],
            // spec/Media_Library_Technical_Specification.md §8 — media_uuids chỉ dùng ở create
            // form, remove_media_uuids chỉ dùng ở edit form.
            'media_uuids' => ['nullable', 'array', 'max:'.OcopProduct::MAX_IMAGES],
            'media_uuids.*' => ['string', 'uuid'],
            'documents' => ['nullable', 'array:'.implode(',', OcopProduct::DOCUMENT_COLLECTIONS)],
            'documents.*' => ['array', 'max:'.OcopProduct::MAX_DOCUMENTS],
            'documents.*.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:20480'],
            'remove_media_uuids' => ['nullable', 'array'],
            'remove_media_uuids.*' => ['string', 'uuid'],
            'purchase_url' => ['nullable', 'url', 'max:500'],
            'status' => ['required', Rule::in(array_column(OcopProductStatus::cases(), 'value'))],
            'is_featured' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ], [
            'category_id.required' => 'Vui lòng chọn danh mục.',
            'category_id.exists' => 'Danh mục được chọn không hợp lệ.',
            'name.required' => 'Vui lòng nhập tên sản phẩm.',
            'name.max' => 'Tên sản phẩm không được vượt quá :max ký tự.',
            'star_rating.required' => 'Vui lòng chọn hạng sao.',
            'star_rating.in' => 'Hạng sao không hợp lệ — chỉ chấp nhận 3, 4 hoặc 5 sao.',
            'ocop_subject_id.required' => 'Vui lòng chọn chủ thể sản xuất.',
            'ocop_subject_id.exists' => 'Chủ thể được chọn không hợp lệ.',
            'media_uuids.max' => 'Tối đa :max ảnh cho mỗi sản phẩm.',
            'documents.*.max' => 'Tối đa :max tệp cho mỗi loại hồ sơ.',
            'documents.*.*.file' => 'Tệp tải lên không hợp lệ.',
            'documents.*.*.mimes' => 'Chỉ chấp nhận tệp PDF, JPG, PNG hoặc WEBP.',
            'documents.*.*.max' => 'Mỗi tệp tối đa 20MB.',
            'origin.max' => 'Xuất xứ không được vượt quá :max ký tự.',
            'production_date.max' => 'Ngày sản xuất không được vượt quá :max ký tự.',
            'shelf_life.max' => 'Hạn sử dụng không được vượt quá :max ký tự.',
            'purchase_url.url' => 'URL không hợp lệ — phải bắt đầu bằng https://',
            'purchase_url.max' => 'URL không được vượt quá :max ký tự.',
            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.in' => 'Trạng thái không hợp lệ.',
            'sort_order.integer' => 'Thứ tự hiển thị phải là số nguyên.',
            'sort_order.min' => 'Thứ tự hiển thị không được âm.',
        ]);
    }
}

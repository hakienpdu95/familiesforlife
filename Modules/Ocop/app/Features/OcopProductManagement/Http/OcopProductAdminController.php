<?php

namespace Modules\Ocop\Features\OcopProductManagement\Http;

use App\Http\Controllers\Controller;
use App\Models\Province;
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
use Modules\Ocop\Features\OcopProductManagement\Actions\ImportOcopProductsAction;
use Modules\Ocop\Features\OcopProductManagement\Actions\RestoreOcopProductAction;
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
        $categoryTree = OcopCategory::flatTree();
        $provinces = Province::where('is_active', true)->orderBy('name')->get(['province_code', 'name']);

        return view('ocop::admin.products.index', compact('categoryTree', 'provinces'));
    }

    public function import(Request $request, ImportOcopProductsAction $action): RedirectResponse
    {
        $this->authorize('create', OcopProduct::class);

        $validated = $request->validate([
            'province_code' => ['required', 'string', 'size:2', 'exists:provinces,province_code'],
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:10240'],
        ], [
            'province_code.required' => 'Vui lòng chọn tỉnh/thành.',
            'province_code.exists' => 'Tỉnh/thành được chọn không hợp lệ.',
            'file.required' => 'Vui lòng chọn file Excel.',
            'file.mimes' => 'Chỉ chấp nhận file .xlsx hoặc .csv.',
            'file.max' => 'File tối đa 10MB.',
        ]);

        $result = $action->handle($request->file('file')->getRealPath(), $validated['province_code']);

        return redirect()->route('backend.ocop.products.index')
            ->with('success', "Import xong: thêm mới {$result['created']}, cập nhật hạng sao {$result['updated']}, không đổi {$result['unchanged']} sản phẩm, ".count($result['errors']).' dòng lỗi.')
            ->with('import_errors', $result['errors']);
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
        $data = OcopProductData::from(Arr::except($validated, 'document_uuids'));
        $product = $action->handle($data);
        $storeDocuments->handle($product, $validated['document_uuids'] ?? [], (int) $request->user()->id);

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
        $data = OcopProductData::from(Arr::except($validated, 'document_uuids'));
        $action->handle($product, $data);
        $storeDocuments->handle($product, $validated['document_uuids'] ?? [], (int) $request->user()->id);

        return redirect()->route('backend.ocop.products.index')
            ->with('success', 'Cập nhật sản phẩm thành công.');
    }

    public function restore(Request $request, OcopProduct $product, RestoreOcopProductAction $action): RedirectResponse|JsonResponse
    {
        $this->authorize('restore', $product);

        abort_unless($product->trashed(), 422, 'Mục này chưa bị xoá.');

        $action->handle($product);

        $message = 'Đã khôi phục sản phẩm "'.$product->name.'".';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('backend.ocop.products.edit', $product)->with('success', $message);
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

        foreach ($validated['document_uuids'] ?? [] as $collection => $files) {
            $kept = $product->getMedia($collection)->whereNotIn('uuid', $removed)->count();

            if ($kept + count($files) > OcopProduct::MAX_DOCUMENTS) {
                throw ValidationException::withMessages([
                    "document_uuids.$collection" => 'Tối đa '.OcopProduct::MAX_DOCUMENTS." tệp cho mỗi loại hồ sơ (hiện có {$kept} tệp).",
                ]);
            }
        }
    }

    private function validated(Request $request): array
    {
        $request->request->remove('qr_code');

        if (is_string($request->input('media_uuids'))) {
            $request->merge(['media_uuids' => json_decode($request->input('media_uuids'), true) ?: []]);
        }

        $documentUuids = [];
        foreach ((array) $request->input('document_uuids', []) as $collection => $value) {
            $value = is_string($value) ? (json_decode($value, true) ?: []) : $value;
            if (is_array($value) && $value !== []) {
                $documentUuids[$collection] = array_values($value);
            }
        }
        $request->merge(['document_uuids' => $documentUuids]);

        foreach (['media_uuids', 'remove_media_uuids'] as $field) {
            if (! is_array($request->input($field))) {
                $request->merge([$field => []]);
            }
        }

        $urlRule = ['nullable', 'url', 'regex:/^https?:\/\//i', 'max:500'];

        $validated = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:ocop_categories,id'],
            'name' => ['required', 'string', 'max:150'],
            // §4.2 — chương trình OCOP quốc gia chỉ chấm từ 3 sao trở lên mới được công nhận.
            'star_rating' => ['required', 'in:3,4,5'],
            'description' => ['nullable', 'string'],
            'story' => ['nullable', 'string', 'max:65000'],
            'origin' => ['nullable', 'string', 'max:255'],
            'production_date' => ['nullable', 'string', 'max:100'],
            'shelf_life_value' => ['nullable', 'integer', 'min:1', 'max:9999', 'required_with:shelf_life_unit'],
            'shelf_life_unit' => ['nullable', Rule::in(array_keys(OcopProduct::SHELF_LIFE_UNITS)), 'required_with:shelf_life_value'],
            'ingredients' => ['nullable', 'string', 'max:20000'],
            'usage_instructions' => ['nullable', 'string', 'max:20000'],
            'storage_instructions' => ['nullable', 'string', 'max:20000'],
            'ocop_subject_id' => ['required', 'integer', Rule::exists('ocop_subjects', 'id')->whereNull('deleted_at')],
            // spec/Heritage_Technical_Specification.md §8.2 — tuỳ chọn.
            'heritage_site_id' => ['nullable', 'integer', 'exists:heritage_sites,id'],
            // spec/Media_Library_Technical_Specification.md §8 — media_uuids chỉ dùng ở create
            // form, remove_media_uuids chỉ dùng ở edit form.
            'media_uuids' => ['nullable', 'array', 'max:'.OcopProduct::MAX_IMAGES],
            'media_uuids.*' => ['string', 'uuid'],
            'document_uuids' => ['array:'.implode(',', OcopProduct::DOCUMENT_COLLECTIONS)],
            'document_uuids.*' => ['array', 'max:'.OcopProduct::MAX_DOCUMENTS],
            'document_uuids.*.*' => ['string', 'uuid'],
            'remove_media_uuids' => ['nullable', 'array'],
            'remove_media_uuids.*' => ['string', 'uuid'],
            'shopee_url' => $urlRule,
            'tiktok_url' => $urlRule,
            'status' => ['required', Rule::in(array_column(OcopProductStatus::cases(), 'value'))],
            'is_featured' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ], [
            'category_id.exists' => 'Danh mục được chọn không hợp lệ.',
            'name.required' => 'Vui lòng nhập tên sản phẩm.',
            'name.max' => 'Tên sản phẩm không được vượt quá :max ký tự.',
            'star_rating.required' => 'Vui lòng chọn hạng sao.',
            'star_rating.in' => 'Hạng sao không hợp lệ — chỉ chấp nhận 3, 4 hoặc 5 sao.',
            'ocop_subject_id.required' => 'Vui lòng chọn chủ thể sản xuất.',
            'ocop_subject_id.exists' => 'Chủ thể được chọn không hợp lệ.',
            'media_uuids.max' => 'Tối đa :max ảnh cho mỗi sản phẩm.',
            'document_uuids.*.max' => 'Tối đa :max tệp cho mỗi loại hồ sơ.',
            'document_uuids.*.*.uuid' => 'Tệp hồ sơ tải lên không hợp lệ, vui lòng tải lại.',
            'origin.max' => 'Xuất xứ không được vượt quá :max ký tự.',
            'production_date.max' => 'Ngày sản xuất không được vượt quá :max ký tự.',
            'shelf_life_value.integer' => 'Hạn sử dụng phải là số nguyên.',
            'shelf_life_value.min' => 'Hạn sử dụng phải lớn hơn 0.',
            'shelf_life_value.max' => 'Hạn sử dụng tối đa :max.',
            'shelf_life_value.required_with' => 'Vui lòng nhập số cho hạn sử dụng.',
            'shelf_life_unit.in' => 'Đơn vị hạn sử dụng không hợp lệ.',
            'shelf_life_unit.required_with' => 'Vui lòng chọn đơn vị cho hạn sử dụng.',
            'shopee_url.url' => 'Link Shopee không đúng định dạng URL.',
            'shopee_url.regex' => 'Link Shopee phải bắt đầu bằng http:// hoặc https://.',
            'shopee_url.max' => 'Link Shopee không được vượt quá :max ký tự.',
            'tiktok_url.url' => 'Link TikTok Shop không đúng định dạng URL.',
            'tiktok_url.regex' => 'Link TikTok Shop phải bắt đầu bằng http:// hoặc https://.',
            'tiktok_url.max' => 'Link TikTok Shop không được vượt quá :max ký tự.',
            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.in' => 'Trạng thái không hợp lệ.',
            'sort_order.integer' => 'Thứ tự hiển thị phải là số nguyên.',
            'sort_order.min' => 'Thứ tự hiển thị không được âm.',
        ]);

        $validated['shelf_life'] = OcopProduct::composeShelfLife($validated['shelf_life_value'] ?? null, $validated['shelf_life_unit'] ?? null);

        return $validated;
    }
}

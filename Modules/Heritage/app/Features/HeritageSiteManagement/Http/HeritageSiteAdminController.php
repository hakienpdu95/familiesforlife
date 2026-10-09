<?php

namespace Modules\Heritage\Features\HeritageSiteManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Heritage\Enums\HeritageRank;
use Modules\Heritage\Enums\HeritageSiteStatus;
use Modules\Heritage\Enums\HeritageType;
use Modules\Heritage\Enums\HeritageVisitingStatus;
use Modules\Heritage\Features\HeritageSiteManagement\Actions\CreateHeritageSiteAction;
use Modules\Heritage\Features\HeritageSiteManagement\Actions\DeleteHeritageSiteAction;
use Modules\Heritage\Features\HeritageSiteManagement\Actions\UpdateHeritageSiteAction;
use Modules\Heritage\Features\HeritageSiteManagement\Data\HeritageSiteData;
use Modules\Heritage\Models\HeritageSite;

/** spec/Heritage_Technical_Specification.md §5.1 — mirror 1-1 OcopProductAdminController (draft/published, không có bước duyệt). */
class HeritageSiteAdminController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(HeritageSite::class, 'site');
    }

    /** Dữ liệu bảng lấy qua HeritageSiteApiController (Tabulator, remote pagination/sort/filter). */
    public function index(): View
    {
        return view('heritage::admin.sites.index');
    }

    public function create(): View
    {
        $heritageTypes = HeritageType::cases();
        $ranks = HeritageRank::cases();
        $visitingStatuses = HeritageVisitingStatus::cases();

        return view('heritage::admin.sites.create', compact('heritageTypes', 'ranks', 'visitingStatuses'));
    }

    public function store(Request $request, CreateHeritageSiteAction $action): RedirectResponse
    {
        $data = HeritageSiteData::from($this->validated($request));
        $site = $action->handle($data);

        $message = $site->status === HeritageSiteStatus::Published
            ? "Đã tạo và xuất bản di tích \"{$site->name}\"."
            : "Đã lưu nháp di tích \"{$site->name}\".";

        return redirect()->route('backend.heritage.sites.index')->with('success', $message);
    }

    public function edit(HeritageSite $site): View
    {
        $heritageTypes = HeritageType::cases();
        $ranks = HeritageRank::cases();
        $visitingStatuses = HeritageVisitingStatus::cases();

        return view('heritage::admin.sites.edit', compact('site', 'heritageTypes', 'ranks', 'visitingStatuses'));
    }

    public function update(Request $request, HeritageSite $site, UpdateHeritageSiteAction $action): RedirectResponse
    {
        $data = HeritageSiteData::from($this->validated($request, $site));
        $wasPublished = $site->status === HeritageSiteStatus::Published;
        $action->handle($site, $data);
        $isPublished = $site->status === HeritageSiteStatus::Published;

        $message = match (true) {
            ! $wasPublished && $isPublished => "Đã xuất bản di tích \"{$site->name}\".",
            $wasPublished && ! $isPublished => "Đã chuyển di tích \"{$site->name}\" về nháp — không còn hiển thị công khai.",
            default => 'Cập nhật di tích thành công.',
        };

        return redirect()->route('backend.heritage.sites.index')->with('success', $message);
    }

    public function destroy(Request $request, HeritageSite $site, DeleteHeritageSiteAction $action): RedirectResponse|JsonResponse
    {
        $action->handle($site);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xoá di tích.']);
        }

        return redirect()->route('backend.heritage.sites.index')
            ->with('success', 'Đã xoá di tích.');
    }

    private function validated(Request $request, ?HeritageSite $site = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:200'],
            // §3.5 — rỗng = tự sinh ở CreateHeritageSiteAction; alpha_dash chỉ áp dụng khi biên
            // tập viên tự nhập tay.
            'slug' => [
                'nullable', 'string', 'max:220', 'alpha_dash',
                Rule::unique('heritage_sites', 'slug')->ignore($site?->id),
            ],
            'heritage_type' => ['required', 'string', Rule::in(array_column(HeritageType::cases(), 'value'))],
            'rank' => ['required', 'string', Rule::in(array_column(HeritageRank::cases(), 'value'))],
            'era' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:3000'],
            'content' => ['nullable', 'string', 'max:65000'],
            'province_code' => ['nullable', 'string', 'exists:provinces,province_code'],
            'ward_code' => ['nullable', 'string', 'exists:wards,ward_code'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            // spec/Media_Library_Technical_Specification.md §8 — chỉ dùng ở create form.
            'cover_media_uuid' => ['nullable', 'string'],
            'visiting_status' => ['nullable', 'string', Rule::in(array_column(HeritageVisitingStatus::cases(), 'value'))],
            'status' => ['required', 'string', Rule::in(array_column(HeritageSiteStatus::cases(), 'value'))],
            'is_featured' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ], [
            'name.required' => 'Vui lòng nhập tên di tích.',
            'name.max' => 'Tên di tích không được vượt quá :max ký tự.',
            'slug.unique' => 'Đường dẫn (slug) này đã được dùng — vui lòng chọn giá trị khác hoặc để trống để tự sinh.',
            'slug.max' => 'Đường dẫn (slug) không được vượt quá :max ký tự.',
            'slug.alpha_dash' => 'Đường dẫn (slug) chỉ được chứa chữ, số, dấu - và _.',
            'heritage_type.required' => 'Vui lòng chọn loại hình di tích.',
            'heritage_type.in' => 'Loại hình di tích không hợp lệ.',
            'rank.required' => 'Vui lòng chọn xếp hạng.',
            'rank.in' => 'Xếp hạng không hợp lệ.',
            'era.max' => 'Niên đại không được vượt quá :max ký tự.',
            'description.max' => 'Mô tả không được vượt quá :max ký tự.',
            'content.max' => 'Nội dung quá dài (tối đa :max ký tự, tính cả mã HTML).',
            'province_code.exists' => 'Tỉnh/thành được chọn không hợp lệ.',
            'ward_code.exists' => 'Phường/xã được chọn không hợp lệ.',
            'address.max' => 'Địa chỉ chi tiết không được vượt quá :max ký tự.',
            'latitude.numeric' => 'Vĩ độ phải là số.',
            'latitude.between' => 'Vĩ độ phải trong khoảng -90 đến 90.',
            'longitude.numeric' => 'Kinh độ phải là số.',
            'longitude.between' => 'Kinh độ phải trong khoảng -180 đến 180.',
            'visiting_status.in' => 'Tình trạng tham quan không hợp lệ.',
            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.in' => 'Trạng thái không hợp lệ.',
            'is_featured.boolean' => 'Giá trị "Di tích nổi bật" không hợp lệ.',
            'sort_order.integer' => 'Thứ tự hiển thị phải là số nguyên.',
            'sort_order.min' => 'Thứ tự hiển thị không được âm.',
        ]);
    }
}

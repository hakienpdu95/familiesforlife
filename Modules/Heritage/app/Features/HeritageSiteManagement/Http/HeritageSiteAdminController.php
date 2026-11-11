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
use Modules\Heritage\Features\HeritageSiteManagement\Actions\RestoreHeritageSiteAction;
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
            ? __('Đã tạo và xuất bản di tích ":name".', ['name' => $site->name])
            : __('Đã lưu nháp di tích ":name".', ['name' => $site->name]);

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
            ! $wasPublished && $isPublished => __('Đã xuất bản di tích ":name".', ['name' => $site->name]),
            $wasPublished && ! $isPublished => __('Đã chuyển di tích ":name" về nháp — không còn hiển thị công khai.', ['name' => $site->name]),
            default => __('Cập nhật di tích thành công.'),
        };

        return redirect()->route('backend.heritage.sites.index')->with('success', $message);
    }

    public function restore(Request $request, HeritageSite $site, RestoreHeritageSiteAction $action): RedirectResponse|JsonResponse
    {
        $this->authorize('restore', $site);

        abort_unless($site->trashed(), 422, __('Mục này chưa bị xoá.'));

        $action->handle($site);

        $message = __('Đã khôi phục di tích ":name".', ['name' => $site->name]);

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('backend.heritage.sites.edit', $site)->with('success', $message);
    }

    public function destroy(Request $request, HeritageSite $site, DeleteHeritageSiteAction $action): RedirectResponse|JsonResponse
    {
        $action->handle($site);

        if ($request->expectsJson()) {
            return response()->json(['message' => __('Đã xoá di tích.')]);
        }

        return redirect()->route('backend.heritage.sites.index')
            ->with('success', __('Đã xoá di tích.'));
    }

    private function validated(Request $request, ?HeritageSite $site = null): array
    {
        $request->request->remove('qr_code');

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
            'name.required' => __('Vui lòng nhập tên di tích.'),
            'name.max' => __('Tên di tích không được vượt quá :max ký tự.'),
            'slug.unique' => __('Đường dẫn (slug) này đã được dùng — vui lòng chọn giá trị khác hoặc để trống để tự sinh.'),
            'slug.max' => __('Đường dẫn (slug) không được vượt quá :max ký tự.'),
            'slug.alpha_dash' => __('Đường dẫn (slug) chỉ được chứa chữ, số, dấu - và _.'),
            'heritage_type.required' => __('Vui lòng chọn loại hình di tích.'),
            'heritage_type.in' => __('Loại hình di tích không hợp lệ.'),
            'rank.required' => __('Vui lòng chọn xếp hạng.'),
            'rank.in' => __('Xếp hạng không hợp lệ.'),
            'era.max' => __('Niên đại không được vượt quá :max ký tự.'),
            'description.max' => __('Mô tả không được vượt quá :max ký tự.'),
            'content.max' => __('Nội dung quá dài (tối đa :max ký tự, tính cả mã HTML).'),
            'province_code.exists' => __('Tỉnh/thành được chọn không hợp lệ.'),
            'ward_code.exists' => __('Phường/xã được chọn không hợp lệ.'),
            'address.max' => __('Địa chỉ chi tiết không được vượt quá :max ký tự.'),
            'latitude.numeric' => __('Vĩ độ phải là số.'),
            'latitude.between' => __('Vĩ độ phải trong khoảng -90 đến 90.'),
            'longitude.numeric' => __('Kinh độ phải là số.'),
            'longitude.between' => __('Kinh độ phải trong khoảng -180 đến 180.'),
            'visiting_status.in' => __('Tình trạng tham quan không hợp lệ.'),
            'status.required' => __('Vui lòng chọn trạng thái.'),
            'status.in' => __('Trạng thái không hợp lệ.'),
            'is_featured.boolean' => __('Giá trị "Di tích nổi bật" không hợp lệ.'),
            'sort_order.integer' => __('Thứ tự hiển thị phải là số nguyên.'),
            'sort_order.min' => __('Thứ tự hiển thị không được âm.'),
        ]);
    }
}

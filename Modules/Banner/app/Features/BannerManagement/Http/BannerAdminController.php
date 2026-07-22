<?php

namespace Modules\Banner\Features\BannerManagement\Http;

use App\Http\Controllers\Controller;
use App\Models\Province;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Banner\Features\BannerManagement\Actions\CreateBannerAction;
use Modules\Banner\Features\BannerManagement\Actions\DeleteBannerAction;
use Modules\Banner\Features\BannerManagement\Actions\UpdateBannerAction;
use Modules\Banner\Features\BannerManagement\Data\BannerData;
use Modules\Banner\Models\Banner;
use Modules\Post\Models\PostCategory;

/**
 * spec/Banner_Management_Technical_Specification.md §6 — không có bước duyệt (khác Post/Event),
 * tạo xong hiển thị ngay nếu is_active=true và trong khoảng ngày hợp lệ.
 */
class BannerAdminController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Banner::class, 'banner');
    }

    /** Dữ liệu bảng lấy qua BannerApiController (Tabulator, remote pagination/sort/filter). */
    public function index(): View
    {
        $placements = config('banner.placements');
        $targetTypes = config('banner.target_types');

        return view('banner::admin.banners.index', compact('placements', 'targetTypes'));
    }

    public function create(): View
    {
        $placements = config('banner.placements');
        $targetTypes = config('banner.target_types');
        $categories = PostCategory::active()->orderBy('name')->get(['id', 'slug', 'name']);
        $provinces = Province::orderBy('name')->get(['province_code', 'name']);

        return view('banner::admin.banners.create', compact('placements', 'targetTypes', 'categories', 'provinces'));
    }

    public function store(Request $request, CreateBannerAction $action): RedirectResponse
    {
        $data   = BannerData::from($this->validated($request, imageRequired: true));
        $banner = $action->handle($data);

        return redirect()->route('backend.banner.items.index')
            ->with('success', 'Đã tạo banner mới.' . ($banner->title ? " (\"{$banner->title}\")" : ''));
    }

    public function edit(Banner $banner): View
    {
        $placements = config('banner.placements');
        $targetTypes = config('banner.target_types');
        $categories = PostCategory::active()->orderBy('name')->get(['id', 'slug', 'name']);
        $provinces = Province::orderBy('name')->get(['province_code', 'name']);

        return view('banner::admin.banners.edit', compact('banner', 'placements', 'targetTypes', 'categories', 'provinces'));
    }

    public function update(Request $request, Banner $banner): RedirectResponse
    {
        $data = BannerData::from($this->validated($request, imageRequired: false));
        app(UpdateBannerAction::class)->handle($banner, $data);

        return redirect()->route('backend.banner.items.index')
            ->with('success', 'Cập nhật banner thành công.');
    }

    public function destroy(Request $request, Banner $banner, DeleteBannerAction $action): RedirectResponse|JsonResponse
    {
        $action->handle($banner);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xoá banner.']);
        }

        return redirect()->route('backend.banner.items.index')
            ->with('success', 'Đã xoá banner.');
    }

    private function validated(Request $request, bool $imageRequired): array
    {
        $validated = $request->validate([
            'placement'       => ['required', Rule::in(Banner::validPlacementKeys())],
            'target_type'     => ['required', Rule::in(array_keys(config('banner.target_types')))],
            // spec/Province_Showcase_Technical_Specification.md §3.5 — target_value validate
            // theo đúng target_type: 'category' tra post_categories.slug, 'province' tra
            // provinces.province_code (KHÔNG dùng chung 1 rule như trước khi chỉ có category).
            'target_value'    => [
                Rule::requiredIf(fn () => in_array($request->input('target_type'), ['category', 'province'], true)),
                'nullable', 'string',
                $request->input('target_type') === 'province'
                    ? Rule::exists('provinces', 'province_code')
                    : Rule::exists('post_categories', 'slug')->where('is_active', true),
            ],
            'title'           => ['nullable', 'string', 'max:150'],
            // spec/Media_Library_Technical_Specification.md §8 — chỉ dùng ở create form (form
            // sửa gắn ảnh trực tiếp qua FilePond context header, không qua field này).
            'cover_media_uuid' => [$imageRequired ? 'required' : 'nullable', 'string'],
            'alt_text'        => ['nullable', 'string', 'max:255'],
            'link_url'        => ['nullable', 'url', 'max:2048'],
            'open_in_new_tab' => ['boolean'],
            'badge_label'     => ['nullable', 'string', 'max:40'],
            'start_date'      => ['nullable', 'date'],
            'end_date'        => ['nullable', 'date', 'after_or_equal:start_date'],
            'sort_order'      => ['integer', 'min:0'],
            'is_active'       => ['boolean'],
        ]);

        // Form gửi 'global'/'category' (§4.2 quy ước UI) — 'global' nghĩa là "không targeting",
        // lưu DB target_type=null, target_value=null (KHÔNG lưu chuỗi 'global').
        if ($validated['target_type'] === 'global') {
            $validated['target_type']  = null;
            $validated['target_value'] = null;
        }

        return $validated;
    }
}

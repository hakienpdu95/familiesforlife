<?php

namespace Modules\Banner\Features\BannerManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Banner\Features\BannerManagement\Actions\CreateBannerAction;
use Modules\Banner\Features\BannerManagement\Actions\DeleteBannerAction;
use Modules\Banner\Features\BannerManagement\Actions\StoreBannerImageAction;
use Modules\Banner\Features\BannerManagement\Actions\UpdateBannerAction;
use Modules\Banner\Features\BannerManagement\Data\BannerData;
use Modules\Banner\Features\BannerManagement\Queries\ListBannersForAdminHandler;
use Modules\Banner\Features\BannerManagement\Queries\ListBannersForAdminQuery;
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

    public function index(Request $request, ListBannersForAdminHandler $handler): View
    {
        $banners = $handler->handle(new ListBannersForAdminQuery(
            placement: $request->string('placement')->value() ?: null,
            targetType: $request->string('target_type')->value() ?: null,
            page: max(1, $request->integer('page', 1)),
        ));

        $placements = config('banner.placements');
        $targetTypes = config('banner.target_types');

        return view('banner::admin.banners.index', compact('banners', 'placements', 'targetTypes'));
    }

    public function create(): View
    {
        $placements = config('banner.placements');
        $targetTypes = config('banner.target_types');
        $categories = PostCategory::active()->orderBy('name')->get(['id', 'slug', 'name']);

        return view('banner::admin.banners.create', compact('placements', 'targetTypes', 'categories'));
    }

    public function store(Request $request, StoreBannerImageAction $storeImage, CreateBannerAction $action): RedirectResponse
    {
        $validated = $this->validated($request, imageRequired: true);
        unset($validated['image']);
        $image = $storeImage->handle($request->file('image'));

        $data   = BannerData::from([...$validated, ...$image]);
        $banner = $action->handle($data);

        return redirect()->route('backend.banner.items.index')
            ->with('success', 'Đã tạo banner mới.' . ($banner->title ? " (\"{$banner->title}\")" : ''));
    }

    public function edit(Banner $banner): View
    {
        $placements = config('banner.placements');
        $targetTypes = config('banner.target_types');
        $categories = PostCategory::active()->orderBy('name')->get(['id', 'slug', 'name']);

        return view('banner::admin.banners.edit', compact('banner', 'placements', 'targetTypes', 'categories'));
    }

    public function update(Request $request, Banner $banner, StoreBannerImageAction $storeImage): RedirectResponse
    {
        $validated = $this->validated($request, imageRequired: false);
        unset($validated['image']);
        $image = $request->hasFile('image')
            ? $storeImage->handle($request->file('image'))
            : ['image_path' => null, 'image_width' => null, 'image_height' => null, 'image_size_bytes' => null];

        $data = BannerData::from([...$validated, ...$image]);
        app(UpdateBannerAction::class)->handle($banner, $data);

        return redirect()->route('backend.banner.items.index')
            ->with('success', 'Cập nhật banner thành công.');
    }

    public function destroy(Banner $banner, DeleteBannerAction $action): RedirectResponse
    {
        $action->handle($banner);

        return redirect()->route('backend.banner.items.index')
            ->with('success', 'Đã xoá banner.');
    }

    private function validated(Request $request, bool $imageRequired): array
    {
        $validated = $request->validate([
            'placement'       => ['required', Rule::in(Banner::validPlacementKeys())],
            'target_type'     => ['required', Rule::in(array_keys(config('banner.target_types')))],
            'target_value'    => [
                Rule::requiredIf(fn () => $request->input('target_type') === 'category'),
                'nullable', 'string',
                Rule::exists('post_categories', 'slug')->where('is_active', true),
            ],
            'title'           => ['nullable', 'string', 'max:150'],
            'image'           => [$imageRequired ? 'required' : 'nullable', 'image', 'max:2048'],
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

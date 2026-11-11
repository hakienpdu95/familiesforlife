<?php

namespace Modules\ProvinceShowcase\Features\ProvinceManagement\Http;

use App\Http\Controllers\Controller;
use App\Models\Province;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\ProvinceShowcase\Enums\ProvincePlaceType;
use Modules\ProvinceShowcase\Features\ProvinceManagement\Actions\UpdateProvinceAction;
use Modules\ProvinceShowcase\Features\ProvinceManagement\Data\ProvinceData;
use Modules\ProvinceShowcase\Features\ProvinceManagement\Queries\CountFeaturedProvincesHandler;
use Modules\ProvinceShowcase\Features\ProvinceManagement\Queries\CountFeaturedProvincesQuery;
use Modules\ProvinceShowcase\Features\ProvinceManagement\Queries\ListRegionsForFilterHandler;
use Modules\ProvinceShowcase\Features\ProvinceManagement\Queries\ListRegionsForFilterQuery;

class ProvinceAdminController extends Controller
{
    public function index(ListRegionsForFilterHandler $regionHandler, CountFeaturedProvincesHandler $featuredHandler): View
    {
        $this->authorize('viewAny', Province::class);

        $regions = $regionHandler->handle(new ListRegionsForFilterQuery);
        $placeTypes = ProvincePlaceType::options();
        $featuredCount = $featuredHandler->handle(new CountFeaturedProvincesQuery);
        $featuredMax = (int) config('provinceshowcase.featured_max', 5);

        return view('provinceshowcase::admin.provinces.index', compact('regions', 'placeTypes', 'featuredCount', 'featuredMax'));
    }

    public function edit(Province $province, ListRegionsForFilterHandler $regionHandler, CountFeaturedProvincesHandler $featuredHandler): View
    {
        $this->authorize('update', $province);

        $regions = $regionHandler->handle(new ListRegionsForFilterQuery);
        $placeTypes = ProvincePlaceType::options();
        $featuredCount = $featuredHandler->handle(new CountFeaturedProvincesQuery);
        $featuredMax = (int) config('provinceshowcase.featured_max', 5);

        return view('provinceshowcase::admin.provinces.edit', compact('province', 'regions', 'placeTypes', 'featuredCount', 'featuredMax'));
    }

    public function update(Request $request, Province $province, UpdateProvinceAction $action): RedirectResponse
    {
        $this->authorize('update', $province);

        $data = ProvinceData::from($this->validated($request));
        $action->handle($province, $data, $request->file('cover_image'), $request->file('logo'));

        return redirect()->route('backend.provinces.index')
            ->with('success', __('Đã cập nhật ":name".', ['name' => $province->name]));
    }

    private function validated(Request $request): array
    {
        $request->merge([
            'highlight_tags' => collect((array) $request->input('highlight_tags', []))
                ->map(fn ($tag) => trim((string) $tag))
                ->filter()
                ->unique()
                ->values()
                ->all(),
        ]);

        $validated = $request->validate([
            'short_name' => ['required', 'string', 'max:255'],
            'order_column' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'slogan' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_cover_image' => ['nullable', 'boolean'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=64,min_height=64'],
            'remove_logo' => ['nullable', 'boolean'],
            'tvc_video_url' => ['nullable', 'url:http,https', 'max:500'],
            'vr360_map_url' => ['nullable', 'url:http,https', 'max:500'],
            'highlight_tags' => ['array', 'max:10'],
            'highlight_tags.*' => ['string', 'max:50'],
        ], [
            'slogan.max' => __('Khẩu hiệu tối đa :max ký tự.'),
            'description.max' => __('Mô tả tối đa :max ký tự.'),
            'cover_image.image' => __('Ảnh cover phải là tệp ảnh.'),
            'cover_image.mimes' => __('Ảnh cover chỉ chấp nhận JPG, PNG hoặc WEBP.'),
            'cover_image.max' => __('Ảnh cover tối đa 4MB.'),
            'logo.image' => __('Logo phải là tệp ảnh.'),
            'logo.mimes' => __('Logo chỉ chấp nhận JPG, PNG hoặc WEBP.'),
            'logo.max' => __('Logo tối đa 2MB.'),
            'logo.dimensions' => __('Logo tối thiểu 64×64px.'),
            'tvc_video_url.url' => __('Link video TVC không hợp lệ — phải bắt đầu bằng https://'),
            'vr360_map_url.url' => __('Link bản đồ VR360 không hợp lệ — phải bắt đầu bằng https://'),
            'highlight_tags.max' => __('Tối đa :max thẻ nổi bật.'),
            'highlight_tags.*.max' => __('Mỗi thẻ nổi bật tối đa :max ký tự.'),
        ]);

        unset($validated['cover_image'], $validated['logo']);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['remove_cover_image'] = $request->boolean('remove_cover_image');
        $validated['remove_logo'] = $request->boolean('remove_logo');

        return $validated;
    }
}

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
        $action->handle($province, $data);

        return redirect()->route('backend.provinces.index')
            ->with('success', "Đã cập nhật \"{$province->name}\".");
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'short_name' => ['required', 'string', 'max:255'],
            'order_column' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_featured'] = $request->boolean('is_featured');

        return $validated;
    }
}

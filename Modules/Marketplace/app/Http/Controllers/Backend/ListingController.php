<?php

namespace Modules\Marketplace\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Marketplace\Actions\Backend\CloseMktListingAction;
use Modules\Marketplace\Actions\Backend\StoreMktListingAction;
use Modules\Marketplace\Actions\Backend\UpdateMktListingAction;
use Modules\Marketplace\Data\Requests\StoreMktListingData;
use Modules\Marketplace\Enums\EmploymentType;
use Modules\Marketplace\Enums\ExperienceLevel;
use Modules\Marketplace\Enums\ListingStatus;
use Modules\Marketplace\Enums\ListingType;
use Modules\Marketplace\Enums\ListingVisibility;
use Modules\Marketplace\Enums\WorkType;
use Modules\Marketplace\Models\MktListing;
use Modules\Marketplace\Models\MktTag;

class ListingController extends Controller
{
    private function _resolveOrganizations(): array
    {
        $userOrgId = auth()->user()->organization_id;
        if ($userOrgId) {
            return [Organization::where('id', $userOrgId)->get(['id', 'name']), $userOrgId, true];
        }
        return [Organization::orderBy('name')->get(['id', 'name']), null, false];
    }

    public function index(): View
    {
        $this->authorize('viewAny', MktListing::class);

        return view('marketplace::listings.index', [
            'statuses'         => collect(ListingStatus::cases())->map(fn($e) => ['value' => $e->value, 'text' => $e->label()]),
            'listingTypes'     => collect(ListingType::cases())->map(fn($e) => ['value' => $e->value, 'text' => $e->label()]),
            'workTypes'        => collect(WorkType::cases())->map(fn($e) => ['value' => $e->value, 'text' => $e->label()]),
            'experienceLevels' => collect(ExperienceLevel::cases())->map(fn($e) => ['value' => $e->value, 'text' => $e->label()]),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', MktListing::class);

        [$organizations, $defaultOrgId, $orgLocked] = $this->_resolveOrganizations();

        return view('marketplace::listings.create', array_merge(
            $this->formData(),
            ['listing' => new MktListing()],
            compact('organizations', 'defaultOrgId', 'orgLocked'),
        ));
    }

    public function store(Request $request, StoreMktListingAction $action): RedirectResponse
    {
        $this->authorize('create', MktListing::class);

        $data    = StoreMktListingData::validateAndCreate($request->all());
        $listing = $action->handle($data, auth()->id());

        return redirect()
            ->route('backend.marketplace.listings.show', $listing)
            ->with('success', 'Tin đăng đã được tạo thành công.');
    }

    public function show(MktListing $listing): View
    {
        $this->authorize('view', $listing);

        $listing->load(['organization', 'postedBy', 'tags']);

        return view('marketplace::listings.show', compact('listing'));
    }

    public function edit(MktListing $listing): View
    {
        $this->authorize('update', $listing);

        $listing->load('tags');

        return view('marketplace::listings.edit', array_merge(
            $this->formData(),
            compact('listing'),
        ));
    }

    public function update(Request $request, MktListing $listing, UpdateMktListingAction $action): RedirectResponse
    {
        $this->authorize('update', $listing);

        $data = StoreMktListingData::validateAndCreate($request->all());
        $action->handle($listing, $data);

        return redirect()
            ->route('backend.marketplace.listings.show', $listing)
            ->with('success', 'Tin đăng đã được cập nhật.');
    }

    public function close(MktListing $listing, CloseMktListingAction $action): RedirectResponse
    {
        $this->authorize('close', $listing);

        $action->handle($listing);

        return back()->with('success', 'Tin đăng đã được đóng.');
    }

    public function destroy(MktListing $listing): RedirectResponse
    {
        $this->authorize('delete', $listing);

        $listing->delete();

        return redirect()
            ->route('backend.marketplace.listings.index')
            ->with('success', 'Tin đăng đã được xóa.');
    }

    private function formData(): array
    {
        return [
            'listingTypes'     => ListingType::cases(),
            'workTypes'        => WorkType::cases(),
            'employmentTypes'  => EmploymentType::cases(),
            'experienceLevels' => ExperienceLevel::cases(),
            'visibilities'     => ListingVisibility::cases(),
            'tags'             => MktTag::orderBy('name')->get(['id', 'name']),
        ];
    }
}

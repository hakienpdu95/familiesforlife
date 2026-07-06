<?php
namespace Modules\Marketplace\Http\Controllers\Api\Org;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Marketplace\Actions\Backend\CloseMktListingAction;
use Modules\Marketplace\Actions\Backend\ResyncListingAction;
use Modules\Marketplace\Actions\Backend\StoreMktListingAction;
use Modules\Marketplace\Actions\Backend\UpdateMktListingAction;
use Modules\Marketplace\Data\Requests\StoreMktListingData;
use Modules\Marketplace\Http\Resources\MktListingListResource;
use Modules\Marketplace\Models\MktListing;

class OrgListingApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MktListing::class);

        $listings = MktListing::with('organization:id,name,logo_path')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data'      => MktListingListResource::collection($listings->items()),
            'last_page' => $listings->lastPage(),
            'total'     => $listings->total(),
        ]);
    }

    public function store(Request $request, StoreMktListingAction $action): JsonResponse
    {
        $this->authorize('create', MktListing::class);

        $data    = StoreMktListingData::validateAndCreate($request->all());
        $listing = $action->handle($data, auth()->id());

        return response()->json(new MktListingListResource($listing), 201);
    }

    public function update(Request $request, MktListing $listing, UpdateMktListingAction $action): JsonResponse
    {
        $this->authorize('update', $listing);

        $data = StoreMktListingData::validateAndCreate($request->all());
        $action->handle($listing, $data);

        return response()->json(new MktListingListResource($listing->fresh()));
    }

    public function close(MktListing $listing, CloseMktListingAction $action): JsonResponse
    {
        $this->authorize('close', $listing);

        $action->handle($listing);

        return response()->json(['message' => 'Tin đăng đã được đóng.']);
    }

    public function resync(MktListing $listing, ResyncListingAction $action): JsonResponse
    {
        $this->authorize('update', $listing);

        $synced = $action->handle($listing);

        if (! $synced) {
            return response()->json(['message' => 'Không thể re-sync: tin đăng không có JP post.'], 422);
        }

        return response()->json(['message' => 'Đã re-sync từ Job Posting.', 'listing' => new MktListingListResource($listing->fresh())]);
    }
}

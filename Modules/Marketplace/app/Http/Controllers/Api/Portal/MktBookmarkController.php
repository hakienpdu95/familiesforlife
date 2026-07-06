<?php
namespace Modules\Marketplace\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Marketplace\Actions\Portal\ToggleBookmarkAction;
use Modules\Marketplace\Http\Resources\MktListingBookmarkResource;
use Modules\Marketplace\Models\MktListingBookmark;
use Modules\Marketplace\Models\MktListing;

class MktBookmarkController extends Controller
{
    public function myBookmarks(Request $request): JsonResponse
    {
        $applicant = $request->user('marketplace');
        $bookmarks = MktListingBookmark::with(['listing.organization'])
            ->where('applicant_id', $applicant->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data'      => MktListingBookmarkResource::collection($bookmarks->items()),
            'last_page' => $bookmarks->lastPage(),
            'total'     => $bookmarks->total(),
        ]);
    }

    public function toggle(Request $request, string $slug, ToggleBookmarkAction $action): JsonResponse
    {
        $applicant = $request->user('marketplace');

        $listing = MktListing::withoutGlobalScope('tenant')
            ->where('slug', $slug)
            ->firstOrFail();

        $result = $action->handle($listing, $applicant, $request->input('note'));

        return response()->json($result);
    }
}

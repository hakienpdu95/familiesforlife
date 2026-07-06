<?php

namespace Modules\Marketplace\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Marketplace\Http\Resources\MktListingListResource;
use Modules\Marketplace\Models\MktListing;
use Modules\Marketplace\Queries\ListMktListingHandler;
use Modules\Marketplace\Queries\ListMktListingQuery;

class MktListingApiController extends Controller
{
    public function index(Request $request, ListMktListingHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', MktListing::class);

        $sort      = $request->input('sort', []);
        $sortField = $sort[0]['field'] ?? 'created_at';
        $sortDir   = $sort[0]['dir']   ?? 'desc';

        // System Admin sees all orgs; others see only their own
        $adminScope = auth()->user()?->hasPermissionTo('marketplace.manage') ?? false;

        $query = new ListMktListingQuery(
            page:            max(1, $request->integer('page', 1)),
            perPage:         min(100, max(5, $request->integer('size', 25))),
            sortField:       $sortField,
            sortDir:         $sortDir,
            search:          $request->input('search'),
            status:          $request->input('status'),
            listingType:     $request->input('listing_type'),
            posterType:      $request->input('poster_type'),
            workType:        $request->input('work_type'),
            experienceLevel: $request->input('experience_level'),
            dateFrom:        $request->input('date_from'),
            dateTo:          $request->input('date_to'),
            adminScope:      $adminScope,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => MktListingListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}

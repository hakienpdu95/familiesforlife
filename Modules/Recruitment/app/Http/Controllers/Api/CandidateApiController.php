<?php

namespace Modules\Recruitment\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Recruitment\Http\Resources\CandidateListResource;
use Modules\Recruitment\Queries\ListCandidatesHandler;
use Modules\Recruitment\Queries\ListCandidatesQuery;

class CandidateApiController extends Controller
{
    public function index(Request $request, ListCandidatesHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', \Modules\Recruitment\Models\RcCandidate::class);

        $sort      = $request->input('sort', []);
        $sortField = $sort[0]['field'] ?? 'created_at';
        $sortDir   = $sort[0]['dir']   ?? 'desc';

        $query = new ListCandidatesQuery(
            page:      max(1, $request->integer('page', 1)),
            perPage:   min(100, max(5, $request->integer('size', 25))),
            sortField: $sortField,
            sortDir:   $sortDir,
            search:    $request->input('search'),
            status:    $request->input('status'),
            source:    $request->input('source'),
            dateFrom:  $request->input('date_from'),
            dateTo:    $request->input('date_to'),
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => CandidateListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}

<?php

namespace Modules\JobPosting\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\JobPosting\Http\Resources\JpJobPostListResource;
use Modules\JobPosting\Models\JpJobPost;
use Modules\JobPosting\Queries\ListJpJobPostsHandler;
use Modules\JobPosting\Queries\ListJpJobPostsQuery;

class JpJobPostApiController extends Controller
{
    public function index(Request $request, ListJpJobPostsHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', JpJobPost::class);

        $sort      = $request->input('sort', []);
        $sortField = $sort[0]['field'] ?? 'created_at';
        $sortDir   = $sort[0]['dir']   ?? 'desc';

        $query = new ListJpJobPostsQuery(
            page:            max(1, $request->integer('page', 1)),
            perPage:         min(100, max(5, $request->integer('size', 25))),
            sortField:       $sortField,
            sortDir:         $sortDir,
            search:          $request->input('search'),
            status:          $request->input('status'),
            employmentType:  $request->input('employment_type'),
            workArrangement: $request->input('work_arrangement'),
            experienceLevel: $request->input('experience_level'),
            industry:        $request->input('industry'),
            departmentId:    $request->integer('department_id') ?: null,
            ownerId:         $request->integer('owner_id') ?: null,
            dateFrom:        $request->input('date_from'),
            dateTo:          $request->input('date_to'),
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => JpJobPostListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}

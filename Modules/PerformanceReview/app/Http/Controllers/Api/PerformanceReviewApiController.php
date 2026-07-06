<?php

namespace Modules\PerformanceReview\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\PerformanceReview\Http\Resources\PerformanceReviewListResource;
use Modules\PerformanceReview\Models\PerformanceReview;
use Modules\PerformanceReview\Queries\ListPerformanceReviewsHandler;
use Modules\PerformanceReview\Queries\ListPerformanceReviewsQuery;

class PerformanceReviewApiController extends Controller
{
    public function index(Request $request, ListPerformanceReviewsHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', PerformanceReview::class);

        $sort      = $request->input('sort', []);
        $sortField = $sort[0]['field'] ?? 'created_at';
        $sortDir   = $sort[0]['dir']   ?? 'desc';

        $query = new ListPerformanceReviewsQuery(
            page:       max(1, $request->integer('page', 1)),
            perPage:    min(100, max(5, $request->integer('size', 25))),
            sortField:  $sortField,
            sortDir:    $sortDir,
            search:     $request->input('search'),
            status:     $request->input('status'),
            employeeId: $request->filled('employee_id') ? (int) $request->input('employee_id') : null,
            reviewerId: $request->filled('reviewer_id') ? (int) $request->input('reviewer_id') : null,
            templateId: $request->filled('template_id') ? (int) $request->input('template_id') : null,
            period:     $request->input('period'),
            dateFrom:   $request->input('date_from'),
            dateTo:     $request->input('date_to'),
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => PerformanceReviewListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}

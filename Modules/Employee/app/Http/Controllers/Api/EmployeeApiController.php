<?php

namespace Modules\Employee\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Employee\Http\Resources\EmployeeListResource;
use Modules\Employee\Queries\ListEmployeesHandler;
use Modules\Employee\Queries\ListEmployeesQuery;

class EmployeeApiController extends Controller
{
    public function index(Request $request, ListEmployeesHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', \Modules\Employee\Models\Employee::class);

        $sort      = $request->input('sort', []);
        $sortField = $sort[0]['field'] ?? 'full_name';
        $sortDir   = $sort[0]['dir']   ?? 'asc';

        $query = new ListEmployeesQuery(
            page:           max(1, $request->integer('page', 1)),
            perPage:        min(100, max(5, $request->integer('size', 25))),
            sortField:      $sortField,
            sortDir:        $sortDir,
            search:         $request->input('search'),
            status:         $request->input('status'),
            employmentType: $request->input('employment_type'),
            branchId:       $request->filled('branch_id') ? (int) $request->input('branch_id') : null,
            departmentId:   $request->filled('department_id') ? (int) $request->input('department_id') : null,
            jobTitleId:     $request->filled('job_title_id') ? (int) $request->input('job_title_id') : null,
            dateFrom:       $request->input('date_from'),
            dateTo:         $request->input('date_to'),
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => EmployeeListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}

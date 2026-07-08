<?php

namespace Modules\Report\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Report\Queries\Hr\HeadcountQuery;

class HrReportApiController extends Controller
{
    public function headcount(Request $request): JsonResponse
    {
        $q = HeadcountQuery::fromRequest($request->all());
        return response()->json([
            'summary'              => $q->summary(),
            'by_status'            => $q->byStatus(),
            'by_department'        => $q->byDepartment(),
            'by_branch'            => $q->byBranch(),
            'by_employment_type'   => $q->byEmploymentType(),
            'trend'                => $q->trend(),
            'new_hires'            => $q->newHiresList(),
        ]);
    }
}

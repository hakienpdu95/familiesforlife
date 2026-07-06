<?php

namespace Modules\Survey\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Survey\Http\Resources\ResponseListResource;
use Modules\Survey\Http\Resources\SurveyListResource;
use Modules\Survey\Models\Survey;
use Modules\Survey\Models\SurveyResponse;
use Modules\Survey\Queries\ListSurveysHandler;
use Modules\Survey\Queries\ListSurveysQuery;

class SurveyBackendApiController extends Controller
{
    public function index(Request $request, ListSurveysHandler $handler): JsonResponse
    {
        $this->authorize('survey.view');

        $validated = $request->validate([
            'page'      => ['nullable', 'integer', 'min:1'],
            'size'      => ['nullable', 'integer', 'min:5', 'max:100'],
            'search'    => ['nullable', 'string', 'max:200'],
            'status'    => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to'   => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        // sort[0] is sent by Tabulator as an array element — guard against non-array payloads.
        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'created_at') : 'created_at';
        $sortDir   = is_array($sortRaw) ? (string) ($sortRaw['dir']   ?? 'desc')       : 'desc';

        $status = isset($validated['status']) ? (int) $validated['status'] : null;

        $query = new ListSurveysQuery(
            page:      max(1, (int) ($validated['page'] ?? 1)),
            perPage:   min(100, max(5, (int) ($validated['size'] ?? 25))),
            sortField: $sortField,
            sortDir:   $sortDir,
            search:    $validated['search'] ?? null,
            status:    $status,
            dateFrom:  $validated['date_from'] ?? null,
            dateTo:    $validated['date_to']   ?? null,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => SurveyListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }

    public function responses(Request $request, Survey $survey): JsonResponse
    {
        $this->authorize('survey.view_responses');

        $validated = $request->validate([
            'page'           => ['nullable', 'integer', 'min:1'],
            'size'           => ['nullable', 'integer', 'min:10', 'max:100'],
            'respondent_ref' => ['nullable', 'string', 'max:190'],
            'status'         => ['nullable', 'integer', 'in:0,1'],
            'from'           => ['nullable', 'date_format:Y-m-d'],
            'to'             => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        // sort[0] sent by Tabulator as sort[0][field] / sort[0][dir]
        $sortRaw     = $request->input('sort.0');
        $sortableMap = [
            'id'             => 'id',
            'respondent_ref' => 'respondent_ref',
            'status_value'   => 'status',
            'submitted_at'   => 'submitted_at',
        ];
        $sortKey   = is_array($sortRaw) ? ($sortRaw['field'] ?? 'submitted_at') : 'submitted_at';
        $sortField = $sortableMap[$sortKey] ?? 'submitted_at';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $page    = max(1, (int) ($validated['page'] ?? 1));
        $perPage = min(100, max(10, (int) ($validated['size'] ?? 50)));

        $query = SurveyResponse::forSurvey($survey->id)
            ->select(['id', 'survey_id', 'respondent_ref', 'respondent_ip', 'status', 'submitted_at'])
            ->when(isset($validated['respondent_ref']), fn ($q) =>
                $q->where('respondent_ref', 'like', $validated['respondent_ref'] . '%')
            )
            ->when(isset($validated['status']), fn ($q) =>
                $q->where('status', $validated['status'])
            )
            ->when(isset($validated['from']), fn ($q) =>
                $q->where('submitted_at', '>=', $validated['from'] . ' 00:00:00')
            )
            ->when(isset($validated['to']), fn ($q) =>
                $q->where('submitted_at', '<=', $validated['to'] . ' 23:59:59')
            )
            ->orderBy($sortField, $sortDir)
            ->when($sortField !== 'id', fn ($q) => $q->orderBy('id', $sortDir));

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'      => ResponseListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}

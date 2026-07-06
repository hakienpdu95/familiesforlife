<?php

namespace Modules\Survey\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Survey\Actions\ExportSurveyResponsesAction;
use Modules\Survey\Enums\ResponseStatus;
use Modules\Survey\Models\Survey;
use Modules\Survey\Models\SurveyResponse;
use Modules\Survey\Services\ResponseViewerService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResponseController extends Controller
{
    public function index(Survey $survey)
    {
        $this->authorize('survey.view_responses');

        // Stat cards are server-side rendered (one query); table data is loaded by Tabulator via API.
        $counts = SurveyResponse::forSurvey($survey->id)
            ->selectRaw(
                'COUNT(*) as total_all, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as total_complete',
                [ResponseStatus::Complete->value]
            )
            ->first();

        $totalAll      = (int) ($counts->total_all      ?? 0);
        $totalComplete = (int) ($counts->total_complete ?? 0);

        $statuses = collect(ResponseStatus::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
            ->all();

        return view('survey::responses.index', compact('survey', 'totalAll', 'totalComplete', 'statuses'));
    }

    public function show(Survey $survey, SurveyResponse $response, ResponseViewerService $viewer)
    {
        $this->authorize('survey.view_responses');

        $this->checkOwnership($response, $survey);

        $data = $viewer->build($response);

        return view('survey::responses.show', [
            'survey'   => $survey,
            'response' => $response,
            'sections' => $data['sections'],
        ]);
    }

    public function export(Request $request, Survey $survey, ExportSurveyResponsesAction $action): StreamedResponse|RedirectResponse
    {
        $this->authorize('survey.export');

        $result = $action->handle(
            $survey,
            $request->query('respondent_ref'),
            $request->query('from'),
            $request->query('to'),
        );

        if (is_array($result)) {
            // Job đã được dispatch (> 10.000 rows)
            $key = $result['queued_key'];
            return redirect()
                ->route('backend.surveys.responses.index', $survey)
                ->with('info', "Export đang được xử lý trong nền ({$survey->responses()->complete()->count()} responses). Tải về tại: "
                    . route('backend.surveys.responses.export.download', [$survey, $key]));
        }

        return $result;
    }

    public function downloadExport(Survey $survey, string $key): StreamedResponse|RedirectResponse
    {
        $this->authorize('survey.export');

        // Reject non-UUID keys before constructing the Redis lookup key.
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $key)) {
            abort(404);
        }

        $info = Cache::store('redis')->get("survey:export:{$key}");

        if (!$info || !file_exists($info['path'])) {
            return redirect()
                ->route('backend.surveys.responses.index', $survey)
                ->with('error', 'File export chưa sẵn sàng hoặc đã hết hạn. Vui lòng export lại.');
        }

        return response()->download($info['path'], $info['filename'])->deleteFileAfterSend(true);
    }

    public function destroy(Request $request, Survey $survey, SurveyResponse $response): RedirectResponse|JsonResponse
    {
        $this->authorize('survey.view_responses');

        $this->checkOwnership($response, $survey);

        $response->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Response đã được xóa.']);
        }

        return redirect()
            ->route('backend.surveys.responses.index', $survey)
            ->with('success', 'Response đã được xóa.');
    }

    private function checkOwnership(SurveyResponse $response, Survey $survey): void
    {
        if ($response->survey_id !== $survey->id) {
            abort(404);
        }
    }
}

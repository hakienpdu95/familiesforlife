<?php

namespace Modules\Survey\Http\Controllers;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Modules\Survey\Actions\SubmitSurveyAction;
use Modules\Survey\Data\SurveyAnswerData;
use Modules\Survey\Data\SurveyResponseData;
use Modules\Survey\Enums\ResponseStatus;
use Modules\Survey\Enums\SurveyStatus;
use Modules\Survey\Models\Survey;
use Modules\Survey\Models\SurveyResponse;

class SurveyTakeController extends Controller
{
    public function index(Request $request)
    {
        $orgId = TenantContext::getOrganizationId();

        $surveys = Survey::active()
            ->where('organization_id', $orgId)
            ->orderBy('created_at', 'desc')
            ->get();

        $respondentRef = auth()->user()->email;

        $doneIds = SurveyResponse::whereIn('survey_id', $surveys->pluck('id'))
            ->where('respondent_ref', $respondentRef)
            ->where('status', ResponseStatus::Complete->value)
            ->pluck('survey_id')
            ->toArray();

        return view('survey::surveys.my', compact('surveys', 'doneIds'));
    }

    public function show(Request $request, Survey $survey)
    {
        $isAdmin = $request->user()->can('survey.view');

        if ($survey->status !== SurveyStatus::Active && ! $isAdmin) {
            abort(404, 'Khảo sát không tồn tại hoặc chưa được kích hoạt.');
        }

        $respondentRef = auth()->user()->email;

        // Kiểm tra nếu survey không cho phép làm lại
        $alreadyDone = false;
        if (! $survey->allow_multiple_responses) {
            $alreadyDone = SurveyResponse::forSurvey($survey->id)
                ->where('respondent_ref', $respondentRef)
                ->where('status', ResponseStatus::Complete->value)
                ->exists();
        }

        $sections = $survey->sections()
            ->with([
                'fields' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
                'fields.options' => fn ($q) => $q->orderBy('sort_order'),
                'fields.rows',
            ])
            ->orderBy('sort_order')
            ->get();

        return view('survey::surveys.take', compact('survey', 'sections', 'alreadyDone'));
    }

    public function submit(Request $request, Survey $survey, SubmitSurveyAction $action): RedirectResponse
    {
        if ($survey->status !== SurveyStatus::Active) {
            abort(403, 'Khảo sát chưa được kích hoạt, không thể nộp.');
        }

        $rawAnswers = $request->input('answers', []);
        $rawOther   = $request->input('answers_other', []);

        $answersArray = [];
        foreach ($rawAnswers as $fieldKey => $value) {
            $answersArray[] = [
                'field_key'  => $fieldKey,
                'value'      => is_array($value) ? array_values($value) : $value,
                'other_text' => $rawOther[$fieldKey] ?? null,
            ];
        }

        $data = new SurveyResponseData(
            answers:        SurveyAnswerData::collect($answersArray),
            respondent_ref: auth()->user()->email,
            respondent_ip:  null,
        );

        try {
            $responseId = $action->handle($survey, $data);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()
            ->route('backend.surveys.take.done', ['survey' => $survey->slug, 'responseId' => $responseId])
            ->with('success', 'Cảm ơn bạn đã hoàn thành khảo sát!');
    }

    public function done(Request $request, Survey $survey, int $responseId)
    {
        $response = SurveyResponse::with('result')->find($responseId);

        return view('survey::surveys.take-done', compact('survey', 'response'));
    }
}

<?php

namespace Modules\Deployment\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Deployment\Actions\AssignDataCollectionSurveyAction;
use Modules\Deployment\Actions\ChecklistAutoTickAction;
use Modules\Deployment\Actions\SyncOrgFromSurveyAction;
use Modules\Deployment\Models\DeploymentTarget;
use Modules\Survey\Enums\FieldType;
use Modules\Survey\Enums\ResponseStatus;
use Modules\Survey\Models\SurveyAnswer;
use Modules\Survey\Models\SurveySection;

class DeploymentDataCollectionController extends Controller
{
    // ── Show data collection form ─────────────────────────────────────────────

    public function show(Request $request, DeploymentTarget $target): View
    {
        $this->authorize('update', $target);

        // Auto-assign if not yet assigned (surveyor hits the link directly)
        if (! $target->data_collection_response_id) {
            (new AssignDataCollectionSurveyAction)->handle($target);
            $target->refresh();
        }

        $vertical = $request->attributes->get('_vertical');
        $response = $target->dataCollectionResponse;
        $surveyId = $response->survey_id;

        $sections = SurveySection::where('survey_id', $surveyId)
            ->with(['fields' => fn($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        // Load existing answers keyed by field_id
        $existingAnswers = $response->answers()
            ->join('survey_fields', 'survey_answers.field_id', '=', 'survey_fields.id')
            ->get(['survey_answers.*', 'survey_fields.field_key', 'survey_fields.field_type'])
            ->keyBy('field_id');

        $orgName    = $target->targetOrganization?->name ?? "Target #{$target->id}";
        $isComplete = $response->status instanceof ResponseStatus
            ? $response->status === ResponseStatus::Complete
            : (int) $response->status === 1;

        return view('deployment::data-collection.show', compact(
            'vertical', 'target', 'sections', 'existingAnswers', 'orgName', 'response', 'isComplete'
        ));
    }

    // ── Submit a section ──────────────────────────────────────────────────────

    public function submitSection(Request $request, DeploymentTarget $target, string $sectionCode): RedirectResponse
    {
        $this->authorize('update', $target);

        if (! $target->data_collection_response_id) {
            (new AssignDataCollectionSurveyAction)->handle($target);
            $target->refresh();
        }

        $response = $target->dataCollectionResponse;
        $vertical = $request->attributes->get('_vertical');

        $section = SurveySection::where('survey_id', $response->survey_id)
            ->where('section_code', $sectionCode)
            ->with(['fields'])
            ->firstOrFail();

        $answers = $request->input('answers', []);  // [field_id => value]

        foreach ($section->fields as $field) {
            $raw = $answers[$field->id] ?? null;
            if ($raw === null || $raw === '') {
                continue;
            }

            $answer = SurveyAnswer::firstOrNew([
                'survey_response_id' => $response->id,
                'field_id'           => $field->id,
            ]);

            $this->assignValue($answer, FieldType::from($field->field_type), $raw);
            $answer->save();
        }

        // Mark complete if all sections submitted
        $this->maybeMarkComplete($response);

        // Sync org data and auto-tick checklist
        (new SyncOrgFromSurveyAction)->handle($target, $response->fresh());
        (new ChecklistAutoTickAction)->handle($target, $response->fresh());

        return redirect()
            ->route('deployment.data-collection.show', ['vertical' => $vertical->code(), 'target' => $target->id])
            ->with('success', 'Đã lưu ' . $section->title . '.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function assignValue(SurveyAnswer $answer, FieldType $type, mixed $raw): void
    {
        match ($type) {
            FieldType::Number, FieldType::Rating => $answer->value_number = (float) $raw,
            FieldType::Boolean                   => $answer->value_bool   = filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            FieldType::Date                       => $answer->value_date   = $raw,
            FieldType::Textarea                   => $answer->value_text   = $raw,
            default                               => $answer->value_string = $raw,
        };
    }

    private function maybeMarkComplete($response): void
    {
        // Load all sections for this survey
        $sectionIds = SurveySection::where('survey_id', $response->survey_id)->pluck('id');
        $requiredFieldIds = \Modules\Survey\Models\SurveyField::whereIn('section_id', $sectionIds)
            ->where('is_required', true)
            ->pluck('id');

        $answeredRequiredCount = SurveyAnswer::where('survey_response_id', $response->id)
            ->whereIn('field_id', $requiredFieldIds)
            ->whereRaw("COALESCE(value_string, value_text, CAST(value_number AS CHAR), CAST(value_bool AS CHAR), CAST(value_date AS CHAR)) IS NOT NULL")
            ->count();

        if ($answeredRequiredCount >= $requiredFieldIds->count() && $requiredFieldIds->count() > 0) {
            $response->update(['status' => ResponseStatus::Complete]);
        }
    }
}

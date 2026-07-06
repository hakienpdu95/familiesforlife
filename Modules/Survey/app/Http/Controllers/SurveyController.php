<?php

namespace Modules\Survey\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Survey\Actions\ActivateSurveyAction;
use Modules\Survey\Actions\CreateSurveyAction;
use Modules\Survey\Actions\UpdateSurveyAction;
use Modules\Survey\Data\SurveyFormData;
use Modules\Survey\Enums\FieldType;
use Modules\Survey\Enums\SurveyStatus;
use Modules\Survey\Http\Requests\SurveyRequest;
use Modules\ActivityLog\Core\ActivityLogger;
use Modules\Survey\Models\Survey;
use Modules\Survey\Models\SurveyTurnstileSite;

class SurveyController extends Controller
{
    // ── Index ──────────────────────────────────────────────────────────

    public function index()
    {
        $this->authorize('survey.view');

        $statuses = collect(SurveyStatus::cases())->map(fn ($s) => [
            'value' => $s->value,
            'text'  => $s->label(),
        ])->all();

        return view('survey::surveys.index', compact('statuses'));
    }

    // ── Create / Store ─────────────────────────────────────────────────

    public function create()
    {
        $this->authorize('survey.create');
        [$organizations, $defaultOrgId, $orgLocked] = $this->_resolveOrganizations();
        $turnstileSites = SurveyTurnstileSite::active()->orderBy('name')->get(['id', 'name']);
        return view('survey::surveys.create', compact('organizations', 'defaultOrgId', 'orgLocked', 'turnstileSites'));
    }

    public function store(SurveyRequest $request, CreateSurveyAction $action): RedirectResponse
    {
        $this->authorize('survey.create');

        $data   = SurveyFormData::from($request->toData());
        $survey = $action->handle($data);

        return redirect()
            ->route('backend.surveys.edit', $survey)
            ->with('success', "Survey \"{$survey->title}\" đã được tạo. Thêm sections và fields để bắt đầu.");
    }

    // ── Edit / Update ──────────────────────────────────────────────────

    public function edit(Survey $survey)
    {
        $this->authorize('survey.update');

        $survey->load([
            'sections'                => fn ($q) => $q->ordered(),
            'sections.fields'         => fn ($q) => $q->ordered(),
            'sections.fields.options' => fn ($q) => $q->ordered(),
        ]);

        $isLocked = $survey->status === SurveyStatus::Active
            && $survey->responses()->complete()->exists();

        $sectionsData = $survey->sections->map(fn ($section) => [
            'id'         => $section->id,
            'title'      => $section->title,
            'icon'       => $section->icon,
            'sort_order' => $section->sort_order,
            'fields'     => $section->fields->map(fn ($field) => [
                'id'              => $field->id,
                'survey_id'       => $field->survey_id,
                'section_id'      => $field->section_id,
                'field_key'       => $field->field_key,
                'label'           => $field->label,
                'field_type'      => $field->field_type->value,
                'is_required'     => $field->is_required,
                'is_active'       => $field->is_active,
                'sort_order'      => $field->sort_order,
                'rule_min'        => $field->rule_min,
                'rule_max'        => $field->rule_max,
                'rule_max_select' => $field->rule_max_select,
                'placeholder'     => $field->placeholder ?? '',
                'options'         => $field->options->map(fn ($o) => [
                    'id'           => $o->id,
                    'option_value' => $o->option_value,
                    'label'        => $o->label,
                    'sort_order'   => $o->sort_order,
                    'is_other'     => $o->is_other,
                ])->all(),
            ])->all(),
        ])->all();

        $fieldTypes = collect(FieldType::cases())->map(fn ($ft) => [
            'value' => $ft->value,
            'label' => $ft->label(),
        ])->all();

        [$organizations, , $orgLocked] = $this->_resolveOrganizations();
        $turnstileSites = SurveyTurnstileSite::active()->orderBy('name')->get(['id', 'name']);

        return view('survey::surveys.edit', compact('survey', 'sectionsData', 'fieldTypes', 'isLocked', 'organizations', 'orgLocked', 'turnstileSites'));
    }

    public function update(SurveyRequest $request, Survey $survey, UpdateSurveyAction $action): RedirectResponse
    {
        $this->authorize('survey.update');

        $data = SurveyFormData::from($request->toData());
        $action->handle($survey, $data);

        return redirect()
            ->route('backend.surveys.edit', $survey)
            ->with('success', 'Thông tin survey đã được cập nhật.');
    }

    // ── Activate ───────────────────────────────────────────────────────

    public function activate(Survey $survey, ActivateSurveyAction $action): RedirectResponse
    {
        $this->authorize('survey.update');

        $action->handle($survey);

        return redirect()
            ->route('backend.surveys.edit', $survey)
            ->with('success', "Survey \"{$survey->title}\" đã được kích hoạt (Active).");
    }

    // ── Helpers ────────────────────────────────────────────────────────

    private function _resolveOrganizations(): array
    {
        $userOrgId = auth()->user()->organization_id;
        if ($userOrgId) {
            return [Organization::where('id', $userOrgId)->get(['id', 'name']), $userOrgId, true];
        }
        return [Organization::orderBy('name')->get(['id', 'name']), null, false];
    }

    // ── Destroy ────────────────────────────────────────────────────────

    public function destroy(Survey $survey): RedirectResponse
    {
        $this->authorize('survey.delete');

        if ($survey->status === SurveyStatus::Active) {
            return back()->with('error', 'Không thể xóa survey đang Active. Hãy đóng survey trước.');
        }

        $title = $survey->title;
        $survey->delete();

        ActivityLogger::warning('Survey', 'survey_deleted', null, ['title' => $title]);

        return redirect()
            ->route('backend.surveys.index')
            ->with('success', "Survey \"{$title}\" đã bị xóa.");
    }
}

<?php

namespace Modules\Survey\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Survey\Actions\BuildSurveySchemaAction;
use Modules\Survey\Actions\CreateFieldAction;
use Modules\Survey\Actions\DeactivateFieldAction;
use Modules\Survey\Actions\DestroyFieldAction;
use Modules\Survey\Actions\ReorderAction;
use Modules\Survey\Actions\UpdateFieldAction;
use Modules\Survey\Data\FieldFormData;
use Modules\Survey\Enums\FieldType;
use Modules\Survey\Models\Survey;
use Modules\Survey\Models\SurveyField;

class FieldController extends Controller
{
    public function store(Request $request, Survey $survey, CreateFieldAction $action): JsonResponse
    {
        $this->authorize('survey.update');

        $validated = $this->validateField($request, $survey->id);
        $data      = $this->buildData($validated);

        $field = $action->handle($survey, $data);

        return response()->json([
            'success' => true,
            'data'    => $this->fieldPayload($field),
            'message' => "Field \"{$field->label}\" đã được thêm.",
        ]);
    }

    public function update(Request $request, Survey $survey, SurveyField $field, UpdateFieldAction $action): JsonResponse
    {
        $this->authorize('survey.update');

        $validated = $this->validateField($request, $survey->id, $field->id);
        $data      = $this->buildData($validated);

        $field = $action->handle($survey, $field, $data);

        return response()->json([
            'success' => true,
            'data'    => $this->fieldPayload($field),
            'message' => 'Field đã được cập nhật.',
        ]);
    }

    public function toggleActive(Survey $survey, SurveyField $field, DeactivateFieldAction $action): JsonResponse
    {
        $this->authorize('survey.update');

        $field = $action->handle($field);

        $msg = $field->is_active ? 'Field đã được kích hoạt.' : 'Field đã bị deactivate.';

        return response()->json([
            'success'   => true,
            'is_active' => $field->is_active,
            'message'   => $msg,
        ]);
    }

    public function destroy(Survey $survey, SurveyField $field, DestroyFieldAction $action): JsonResponse
    {
        $this->authorize('survey.update');

        $action->handle($survey, $field);

        return response()->json([
            'success' => true,
            'message' => "Field \"{$field->label}\" đã được xóa.",
        ]);
    }

    public function reorder(Request $request, Survey $survey, ReorderAction $action): JsonResponse
    {
        $this->authorize('survey.update');

        $validated = $request->validate([
            'items'              => ['required', 'array'],
            'items.*.id'         => ['required', 'integer'],
            'items.*.sort_order' => ['required', 'integer'],
        ]);

        $action->handle('survey_fields', $validated['items']);
        BuildSurveySchemaAction::purgeCache($survey->slug);

        return response()->json(['success' => true]);
    }

    // ── Helpers ───────────────────────────────────────────────────────

    private function validateField(Request $request, int $surveyId, ?int $exceptId = null): array
    {
        return $request->validate([
            'label'           => ['required', 'string', 'max:500'],
            'field_type'      => ['required', 'integer', Rule::in(array_column(FieldType::cases(), 'value'))],
            'section_id'      => ['nullable', 'integer', "exists:survey_sections,id"],
            'is_required'     => ['boolean'],
            'rule_min'        => ['nullable', 'integer'],
            'rule_max'        => ['nullable', 'integer'],
            'rule_max_select' => ['nullable', 'integer', 'min:1'],
            'placeholder'     => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function buildData(array $validated): FieldFormData
    {
        return FieldFormData::from([
            'label'           => $validated['label'],
            'field_type'      => FieldType::from((int) $validated['field_type']),
            'section_id'      => $validated['section_id'] ?? null,
            'is_required'     => (bool) ($validated['is_required'] ?? false),
            'rule_min'        => $validated['rule_min'] ?? null,
            'rule_max'        => $validated['rule_max'] ?? null,
            'rule_max_select' => $validated['rule_max_select'] ?? null,
            'placeholder'     => $validated['placeholder'] ?? null,
        ]);
    }

    private function fieldPayload(SurveyField $field): array
    {
        return [
            'id'              => $field->id,
            'survey_id'       => $field->survey_id,
            'section_id'      => $field->section_id,
            'field_key'       => $field->field_key,
            'label'           => $field->label,
            'field_type'      => $field->field_type->value,
            'field_type_label' => $field->field_type->label(),
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
        ];
    }
}

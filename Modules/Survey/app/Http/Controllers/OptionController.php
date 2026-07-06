<?php

namespace Modules\Survey\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Survey\Actions\BuildSurveySchemaAction;
use Modules\Survey\Actions\CreateOptionAction;
use Modules\Survey\Actions\DestroyOptionAction;
use Modules\Survey\Actions\ReorderAction;
use Modules\Survey\Actions\UpdateOptionAction;
use Modules\Survey\Data\OptionFormData;
use Modules\Survey\Models\Survey;
use Modules\Survey\Models\SurveyField;
use Modules\Survey\Models\SurveyFieldOption;

class OptionController extends Controller
{
    public function store(Request $request, Survey $survey, SurveyField $field, CreateOptionAction $action): JsonResponse
    {
        $this->authorize('survey.update');

        $validated = $this->validateOption($request);

        $option = $action->handle($survey, $field, OptionFormData::from($validated));

        return response()->json([
            'success' => true,
            'data'    => $this->optionPayload($option),
            'message' => 'Option đã được thêm.',
        ]);
    }

    public function update(Request $request, Survey $survey, SurveyField $field, SurveyFieldOption $option, UpdateOptionAction $action): JsonResponse
    {
        $this->authorize('survey.update');

        $validated = $this->validateOption($request);

        $option = $action->handle($survey, $field, $option, OptionFormData::from($validated));

        return response()->json([
            'success' => true,
            'data'    => $this->optionPayload($option),
            'message' => 'Option đã được cập nhật.',
        ]);
    }

    public function destroy(Survey $survey, SurveyField $field, SurveyFieldOption $option, DestroyOptionAction $action): JsonResponse
    {
        $this->authorize('survey.update');

        $action->handle($survey, $option);

        return response()->json(['success' => true, 'message' => 'Option đã bị xóa.']);
    }

    public function reorder(Request $request, Survey $survey, SurveyField $field, ReorderAction $action): JsonResponse
    {
        $this->authorize('survey.update');

        $validated = $request->validate([
            'items'              => ['required', 'array'],
            'items.*.id'         => ['required', 'integer'],
            'items.*.sort_order' => ['required', 'integer'],
        ]);

        $action->handle('survey_field_options', $validated['items']);
        BuildSurveySchemaAction::purgeCache($survey->slug);

        return response()->json(['success' => true]);
    }

    private function validateOption(Request $request): array
    {
        return $request->validate([
            'option_value' => ['required', 'string', 'max:150'],
            'label'        => ['required', 'string', 'max:300'],
            'is_other'     => ['boolean'],
        ]);
    }

    private function optionPayload(SurveyFieldOption $option): array
    {
        return [
            'id'           => $option->id,
            'field_id'     => $option->field_id,
            'option_value' => $option->option_value,
            'label'        => $option->label,
            'sort_order'   => $option->sort_order,
            'is_other'     => $option->is_other,
        ];
    }
}

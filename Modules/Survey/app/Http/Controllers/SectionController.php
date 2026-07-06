<?php

namespace Modules\Survey\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Survey\Actions\BuildSurveySchemaAction;
use Modules\Survey\Actions\CreateSectionAction;
use Modules\Survey\Actions\DestroySectionAction;
use Modules\Survey\Actions\ReorderAction;
use Modules\Survey\Actions\UpdateSectionAction;
use Modules\Survey\Data\SectionFormData;
use Modules\Survey\Models\Survey;
use Modules\Survey\Models\SurveySection;

class SectionController extends Controller
{
    public function store(Request $request, Survey $survey, CreateSectionAction $action): JsonResponse
    {
        $this->authorize('survey.update');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'icon'  => ['nullable', 'string', 'max:50'],
        ]);

        $section = $action->handle($survey, SectionFormData::from($validated));

        return response()->json([
            'success' => true,
            'data'    => $this->sectionPayload($section),
            'message' => 'Section đã được thêm.',
        ]);
    }

    public function update(Request $request, Survey $survey, SurveySection $section, UpdateSectionAction $action): JsonResponse
    {
        $this->authorize('survey.update');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'icon'  => ['nullable', 'string', 'max:50'],
        ]);

        $section = $action->handle($survey, $section, SectionFormData::from($validated));

        return response()->json([
            'success' => true,
            'data'    => $this->sectionPayload($section),
            'message' => 'Section đã được cập nhật.',
        ]);
    }

    public function destroy(Survey $survey, SurveySection $section, DestroySectionAction $action): JsonResponse
    {
        $this->authorize('survey.update');

        $action->handle($survey, $section);

        return response()->json(['success' => true, 'message' => 'Section đã bị xóa.']);
    }

    public function reorder(Request $request, Survey $survey, ReorderAction $action): JsonResponse
    {
        $this->authorize('survey.update');

        $validated = $request->validate([
            'items'              => ['required', 'array'],
            'items.*.id'         => ['required', 'integer'],
            'items.*.sort_order' => ['required', 'integer'],
        ]);

        $action->handle('survey_sections', $validated['items']);
        BuildSurveySchemaAction::purgeCache($survey->slug);

        return response()->json(['success' => true]);
    }

    private function sectionPayload(SurveySection $section): array
    {
        return [
            'id'         => $section->id,
            'title'      => $section->title ?? '',
            'icon'       => $section->icon ?? '',
            'sort_order' => $section->sort_order,
        ];
    }
}

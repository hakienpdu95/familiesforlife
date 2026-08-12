<?php

namespace Modules\AIVideoStudioTemplate\Features\ShotManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\AIVideoStudioTemplate\Features\ShotManagement\Actions\CreateShotAction;
use Modules\AIVideoStudioTemplate\Features\ShotManagement\Actions\DeleteShotAction;
use Modules\AIVideoStudioTemplate\Features\ShotManagement\Actions\ReorderShotsAction;
use Modules\AIVideoStudioTemplate\Features\ShotManagement\Actions\SaveShotAiResultAction;
use Modules\AIVideoStudioTemplate\Features\ShotManagement\Actions\UpdateShotAction;
use Modules\AIVideoStudioTemplate\Features\ShotManagement\Data\ShotInputData;
use Modules\AIVideoStudioTemplate\Features\ShotManagement\Http\Requests\ReorderShotsRequest;
use Modules\AIVideoStudioTemplate\Features\ShotManagement\Http\Requests\SaveShotAiResultRequest;
use Modules\AIVideoStudioTemplate\Features\ShotManagement\Http\Requests\StoreShotRequest;
use Modules\AIVideoStudioTemplate\Features\ShotManagement\Http\Requests\UpdateShotRequest;
use Modules\AIVideoStudioTemplate\Models\AiVideoStudioProject;
use Modules\AIVideoStudioTemplate\Models\AiVideoStudioShot;

/**
 * spec/AIVideoStudioTemplate_Technical_Specification.md §6.1 — JSON dùng cho quản lý Shot inline
 * trên trang show project (fetch, không reload trang). Contract response chốt rõ ở đây, KHÔNG tự
 * chế shape lỗi riêng cho validate (422 mặc định Laravel FormRequest).
 */
class ShotApiController extends Controller
{
    public function store(StoreShotRequest $request, AiVideoStudioProject $project, CreateShotAction $action): JsonResponse
    {
        $data = ShotInputData::from($request->toData());
        $shot = $action->handle($project, $data, $request->user()->id);

        return response()->json($this->toResource($shot), Response::HTTP_CREATED);
    }

    public function update(UpdateShotRequest $request, AiVideoStudioShot $shot, UpdateShotAction $action): JsonResponse
    {
        $data = ShotInputData::from($request->toData());
        $shot = $action->handle($shot, $data, $request->user()->id);

        return response()->json($this->toResource($shot));
    }

    public function destroy(AiVideoStudioShot $shot, DeleteShotAction $action): Response
    {
        $action->handle($shot);

        return response()->noContent();
    }

    public function reorder(ReorderShotsRequest $request, AiVideoStudioProject $project, ReorderShotsAction $action): JsonResponse
    {
        try {
            $action->handle($project, $request->validated('shot_ids'));
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json(['message' => 'Đã cập nhật thứ tự shot.']);
    }

    public function saveResult(SaveShotAiResultRequest $request, AiVideoStudioShot $shot, SaveShotAiResultAction $action): JsonResponse
    {
        $action->handle($shot, $request->validated('ai_result'), $request->user()->id);

        return response()->json(['ai_result' => $shot->fresh()->ai_result]);
    }

    private function toResource(AiVideoStudioShot $shot): array
    {
        return $shot->only([
            'id', 'uuid', 'sort_order', 'label',
            'subject', 'action', 'environment', 'camera', 'style', 'mood', 'duration_seconds', 'timeline_breakdown',
            'audio_direction', 'script_line', 'cta_text', 'constraints',
            'model_tool', 'reference_assets', 'compiled_prompt', 'image_prompt', 'motion_prompt',
            'ai_result', 'qc_notes',
        ]);
    }
}

<?php

namespace Modules\AIVideoStudioTemplate\Features\ShotManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\AIVideoStudioTemplate\Features\ShotManagement\Data\ShotInputData;
use Modules\AIVideoStudioTemplate\Models\AiVideoStudioProject;
use Modules\AIVideoStudioTemplate\Models\AiVideoStudioShot;

/**
 * spec/AIVideoStudioTemplate_Technical_Specification.md §3.2 — anchoring: prefill subject/style/
 * constraints từ default của Project CHỈ khi field đó để trống lúc tạo mới (§0 — "anchoring KHÔNG
 * khoá cứng", vẫn sửa được riêng từng Shot).
 */
class CreateShotAction
{
    use AsAction;

    public function __construct(private readonly BuildShotPromptAction $buildShotPrompt) {}

    public function handle(AiVideoStudioProject $project, ShotInputData $data, int $userId): AiVideoStudioShot
    {
        $shot = $project->shots()->create([
            'sort_order' => $data->sortOrder ?? ((int) $project->shots()->max('sort_order') + 1),
            'label' => $data->label,
            'subject' => $data->subject ?: $project->default_subject,
            'action' => $data->action,
            'environment' => $data->environment,
            'camera' => $data->camera,
            'style' => $data->style ?: $project->default_style,
            'mood' => $data->mood,
            'duration_seconds' => $data->durationSeconds,
            'timeline_breakdown' => $data->timelineBreakdown,
            'audio_direction' => $data->audioDirection,
            'constraints' => $data->constraints ?: $project->default_constraints,
            'script_line' => $data->scriptLine,
            'cta_text' => $data->ctaText,
            'model_tool' => $data->modelTool,
            'reference_assets' => $data->referenceAssets,
            'qc_notes' => $data->qcNotes,
            // v1.12 — nhập tay tự do, KHÔNG tự sinh (đã bỏ BuildShotPromptAction::buildImagePrompt()/
            // buildMotionPrompt() theo phản hồi người dùng), cùng nhóm metadata với model_tool/qc_notes.
            'image_prompt' => $data->imagePrompt,
            'motion_prompt' => $data->motionPrompt,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        // v1.7 — truyền sẵn $project (tránh lazy-load thừa) + lưu null khi prompt rỗng để tài liệu
        // xuất hiện placeholder "chưa có prompt" thay vì 1 câu dẫn không có mô tả nào (§3.5).
        $shot->update([
            'compiled_prompt' => $this->buildShotPrompt->handle($shot, $project) ?: null,
        ]);

        return $shot;
    }
}

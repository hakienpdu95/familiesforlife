<?php

namespace Modules\AIVideoStudioTemplate\Features\ShotManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\AIVideoStudioTemplate\Features\ShotManagement\Data\ShotInputData;
use Modules\AIVideoStudioTemplate\Models\AiVideoStudioShot;

/**
 * spec/AIVideoStudioTemplate_Technical_Specification.md §3.2 — cùng logic build lại
 * `compiled_prompt` với CreateShotAction, nhưng KHÔNG tự prefill lại từ default của Project (anchoring
 * chỉ áp dụng lúc TẠO MỚI — tránh ghi đè field người dùng đã cố tình sửa khác default, §0).
 */
class UpdateShotAction
{
    use AsAction;

    public function __construct(private readonly BuildShotPromptAction $buildShotPrompt) {}

    public function handle(AiVideoStudioShot $shot, ShotInputData $data, int $userId): AiVideoStudioShot
    {
        $shot->update([
            'sort_order' => $data->sortOrder ?? $shot->sort_order,
            'label' => $data->label,
            'subject' => $data->subject,
            'action' => $data->action,
            'environment' => $data->environment,
            'camera' => $data->camera,
            'style' => $data->style,
            'mood' => $data->mood,
            'duration_seconds' => $data->durationSeconds,
            'timeline_breakdown' => $data->timelineBreakdown,
            'audio_direction' => $data->audioDirection,
            'constraints' => $data->constraints,
            'script_line' => $data->scriptLine,
            'cta_text' => $data->ctaText,
            'model_tool' => $data->modelTool,
            'reference_assets' => $data->referenceAssets,
            'qc_notes' => $data->qcNotes,
            // v1.12 — nhập tay tự do, KHÔNG tự sinh (xem BuildShotPromptAction).
            'image_prompt' => $data->imagePrompt,
            'motion_prompt' => $data->motionPrompt,
            'updated_by' => $userId,
        ]);

        // v1.7 — xem ghi chú ở CreateShotAction: rỗng thì lưu null (placeholder ở tài liệu xuất).
        $shot->update([
            'compiled_prompt' => $this->buildShotPrompt->handle($shot, $shot->project) ?: null,
        ]);

        return $shot;
    }
}

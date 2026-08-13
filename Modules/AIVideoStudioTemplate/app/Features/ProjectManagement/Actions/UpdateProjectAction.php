<?php

namespace Modules\AIVideoStudioTemplate\Features\ProjectManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\AIVideoStudioTemplate\Features\ProjectManagement\Data\ProjectInputData;
use Modules\AIVideoStudioTemplate\Features\ShotManagement\Actions\BuildShotPromptAction;
use Modules\AIVideoStudioTemplate\Models\AiVideoStudioProject;
use Modules\AIVideoStudioTemplate\Models\AiVideoStudioShot;

/**
 * spec/AIVideoStudioTemplate_Technical_Specification.md §8.
 *
 * **Hai cơ chế KHÁC NHAU, đừng nhầm (§3.6, v1.7):**
 *
 * 1. `default_subject`/`default_style`/`default_constraints` (anchoring) — SAO CHÉP giá trị vào
 *    field của Shot lúc TẠO MỚI. Sửa ở đây KHÔNG lan sang Shot đã tạo, vì người dùng có thể đã cố ý
 *    sửa field đó khác default; ghi đè sẽ mất công sức của họ (§0).
 * 2. `aspect_ratio`/`resolution`/`video_type`/`video_formula`(v1.16)/`target_audience`/`core_message` (bối cảnh project) —
 *    được RENDER vào `compiled_prompt`, không nằm ở field nào của Shot nên không có gì để mất. Đổi
 *    mà không build lại thì mọi prompt đã lưu nói sai định dạng/tông → PHẢI lan xuống toàn bộ Shot.
 */
class UpdateProjectAction
{
    use AsAction;

    /** Các field của Project được render vào `compiled_prompt` (BuildShotPromptAction). */
    private const PROMPT_CONTEXT_FIELDS = [
        'aspect_ratio', 'resolution', 'video_type', 'video_formula', 'target_audience', 'core_message',
    ];

    public function __construct(private readonly BuildShotPromptAction $buildShotPrompt) {}

    public function handle(AiVideoStudioProject $project, ProjectInputData $data, int $userId): AiVideoStudioProject
    {
        $contextBefore = $project->only(self::PROMPT_CONTEXT_FIELDS);

        $project->update([
            'name' => $data->name,
            'description' => $data->description,
            'objective' => $data->objective,
            'target_audience' => $data->targetAudience,
            'video_type' => $data->videoType,
            'video_formula' => $data->videoFormula,
            'core_message' => $data->coreMessage,
            'aspect_ratio' => $data->aspectRatio,
            'resolution' => $data->resolution,
            'default_subject' => $data->defaultSubject,
            'reference_image_url' => $data->referenceImageUrl,
            'reference_context_prompt' => $data->referenceContextPrompt,
            'default_style' => $data->defaultStyle,
            'default_constraints' => $data->defaultConstraints,
            'status' => $data->status ?: $project->status,
            'updated_by' => $userId,
        ]);

        if ($project->only(self::PROMPT_CONTEXT_FIELDS) !== $contextBefore) {
            $this->rebuildShotPrompts($project);
        }

        return $project;
    }

    /**
     * Build lại `compiled_prompt` cho MỌI shot của project. Chỉ đụng đúng cột `compiled_prompt` —
     * không chạm field nội dung nào của Shot, nên an toàn với shot người dùng đã sửa tay.
     *
     * v1.12 — KHÔNG còn động tới `image_prompt`/`motion_prompt`: 2 field này giờ nhập tay tự do
     * (BuildShotPromptAction không còn tự sinh chúng), nên tự rebuild ở đây sẽ GHI ĐÈ mất nội dung
     * người dùng vừa gõ — đúng lỗi mà cơ chế rebuild này tồn tại để TRÁNH cho field nội dung khác.
     */
    private function rebuildShotPrompts(AiVideoStudioProject $project): void
    {
        $project->shots()->get()->each(function (AiVideoStudioShot $shot) use ($project): void {
            $shot->update([
                'compiled_prompt' => $this->buildShotPrompt->handle($shot, $project) ?: null,
            ]);
        });
    }
}

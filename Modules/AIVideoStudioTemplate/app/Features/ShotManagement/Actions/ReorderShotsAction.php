<?php

namespace Modules\AIVideoStudioTemplate\Features\ShotManagement\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\AIVideoStudioTemplate\Models\AiVideoStudioProject;

/**
 * spec/AIVideoStudioTemplate_Technical_Specification.md §3.4 (v1.1) — ownership check bắt buộc.
 *
 * @param  int[]  $shotIdsInOrder
 *
 * @throws AuthorizationException Nếu có ID không thuộc $project — chặn request thủ công cố cập
 *                                nhật sort_order của shot thuộc project KHÁC (2 project khác nhau đều được phép bởi cùng 1
 *                                permission phẳng 'ai_video_studio_template.use' — không có ranh giới quyền giữa các project,
 *                                nên phải tự kiểm tra ownership ở tầng Action, KHÔNG dựa vào Policy/route-model-binding để chặn
 *                                hộ). all-or-nothing: nếu có 1 ID sai, KHÔNG update sort_order của bất kỳ shot nào trong request.
 */
class ReorderShotsAction
{
    use AsAction;

    public function handle(AiVideoStudioProject $project, array $shotIdsInOrder): void
    {
        $ownedIds = $project->shots()->pluck('id');

        if (collect($shotIdsInOrder)->diff($ownedIds)->isNotEmpty()) {
            throw new AuthorizationException('1 hoặc nhiều shot không thuộc project này.');
        }

        foreach ($shotIdsInOrder as $index => $shotId) {
            $project->shots()->whereKey($shotId)->update(['sort_order' => $index]);
        }
    }
}

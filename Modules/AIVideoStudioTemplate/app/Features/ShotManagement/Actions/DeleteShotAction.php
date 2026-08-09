<?php

namespace Modules\AIVideoStudioTemplate\Features\ShotManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\AIVideoStudioTemplate\Models\AiVideoStudioShot;

/** spec/AIVideoStudioTemplate_Technical_Specification.md §8 — xoá KHÔNG cascade gì thêm, không thể hoàn tác (confirm ở UI). */
class DeleteShotAction
{
    use AsAction;

    public function handle(AiVideoStudioShot $shot): void
    {
        $shot->delete();
    }
}

<?php

namespace Modules\AIVideoStudioTemplate\Features\ProjectManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\AIVideoStudioTemplate\Models\AiVideoStudioProject;

/** spec/AIVideoStudioTemplate_Technical_Specification.md §2.2/§8 — cascadeOnDelete xoá hết Shot con, không thể hoàn tác (confirm ở UI). */
class DeleteProjectAction
{
    use AsAction;

    public function handle(AiVideoStudioProject $project): void
    {
        $project->delete();
    }
}

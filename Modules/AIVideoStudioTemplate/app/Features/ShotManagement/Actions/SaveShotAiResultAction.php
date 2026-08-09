<?php

namespace Modules\AIVideoStudioTemplate\Features\ShotManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\AIVideoStudioTemplate\Models\AiVideoStudioShot;

/** spec/AIVideoStudioTemplate_Technical_Specification.md §3.3 — lưu kết quả dán tay từ AI ngoài (link/text), KHÔNG upload file (§0). */
class SaveShotAiResultAction
{
    use AsAction;

    public function handle(AiVideoStudioShot $shot, ?string $aiResult, int $userId): void
    {
        $shot->update(['ai_result' => $aiResult, 'updated_by' => $userId]);
    }
}

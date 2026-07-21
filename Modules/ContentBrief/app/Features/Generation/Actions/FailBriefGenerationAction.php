<?php

namespace Modules\ContentBrief\Features\Generation\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ContentBrief\Enums\GenerationStatus;
use Modules\ContentBrief\Events\BriefGenerationFailed;
use Modules\ContentBrief\Models\ContentBriefGeneration;

class FailBriefGenerationAction
{
    use AsAction;

    public function handle(ContentBriefGeneration $generation, string $errorMessage): ContentBriefGeneration
    {
        $generation->update([
            'status'        => GenerationStatus::Failed,
            'error_message' => $errorMessage,
        ]);

        event(new BriefGenerationFailed($generation));

        return $generation;
    }
}

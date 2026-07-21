<?php

namespace Modules\ContentBrief\Features\Generation\Actions;

use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ContentBrief\Enums\BriefVersionStatus;
use Modules\ContentBrief\Enums\GenerationStatus;
use Modules\ContentBrief\Events\BriefGenerationRequested;
use Modules\ContentBrief\Models\ContentBrief;
use Modules\ContentBrief\Models\ContentBriefGeneration;

class RequestBriefGenerationAction
{
    use AsAction;

    /**
     * spec/ContentBrief_Technical_Specification.md §6 — validate currentVersion đã approved,
     * tạo 1 dòng content_brief_generations(status=pending). KHÔNG gọi bất kỳ AI provider nào.
     */
    public function handle(ContentBrief $brief): ContentBriefGeneration
    {
        throw_if(
            $brief->currentVersion?->status !== BriefVersionStatus::Approved,
            ValidationException::withMessages([
                'version' => 'Chỉ có thể yêu cầu sinh nội dung khi version hiện tại đã được duyệt.',
            ])
        );

        $generation = ContentBriefGeneration::create([
            'content_brief_version_id' => $brief->current_version_id,
            'organization_id'          => $brief->organization_id,
            'status'                   => GenerationStatus::Pending,
            'requested_at'             => now(),
            'created_by'               => auth()->id(),
        ]);

        event(new BriefGenerationRequested($generation));

        return $generation;
    }
}

<?php

namespace Modules\ContentBrief\Features\Generation\Actions;

use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ContentBrief\Enums\GenerationStatus;
use Modules\ContentBrief\Events\BriefGenerationCompleted;
use Modules\ContentBrief\Features\Generation\Data\GenerationOutputData;
use Modules\ContentBrief\Models\ContentBriefGeneration;

class CompleteBriefGenerationAction
{
    use AsAction;

    /**
     * spec/ContentBrief_Technical_Specification.md §6/§6.1 — điểm dừng cuối cùng của module.
     * $output đã được validate theo GenerationOutputData TRƯỚC khi tới đây (ở Controller, cùng
     * pattern MenuItemData/PageData — validate rồi mới hydrate DTO). KHÔNG tạo/ghi bất kỳ dữ
     * liệu nào ở bảng khác.
     */
    public function handle(ContentBriefGeneration $generation, GenerationOutputData $output): ContentBriefGeneration
    {
        throw_unless(
            in_array($generation->status, [GenerationStatus::Pending, GenerationStatus::Processing], true),
            ValidationException::withMessages([
                'status' => 'Generation này đã hoàn tất hoặc thất bại — không thể ghi output nữa.',
            ])
        );

        $generation->update([
            'output'       => $output->toArray(),
            'status'       => GenerationStatus::Completed,
            'completed_at' => now(),
        ]);

        event(new BriefGenerationCompleted($generation));

        return $generation;
    }
}

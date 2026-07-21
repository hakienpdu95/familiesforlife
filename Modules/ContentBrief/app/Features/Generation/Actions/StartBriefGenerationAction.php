<?php

namespace Modules\ContentBrief\Features\Generation\Actions;

use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ContentBrief\Enums\GenerationStatus;
use Modules\ContentBrief\Models\ContentBriefGeneration;

class StartBriefGenerationAction
{
    use AsAction;

    /**
     * spec/ContentBrief_Technical_Specification.md §6 — tuỳ chọn: cơ chế đứng sau báo "đã nhận
     * việc, đang xử lý". Không bắt buộc gọi — CompleteBriefGenerationAction chấp nhận cả
     * pending lẫn processing.
     */
    public function handle(ContentBriefGeneration $generation): ContentBriefGeneration
    {
        throw_if($generation->status !== GenerationStatus::Pending, ValidationException::withMessages([
            'status' => 'Chỉ chuyển sang "đang xử lý" từ trạng thái đang chờ.',
        ]));

        $generation->update(['status' => GenerationStatus::Processing]);

        return $generation;
    }
}

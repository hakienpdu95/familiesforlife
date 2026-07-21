<?php

namespace Modules\ContentBrief\Features\BriefManagement\Actions;

use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ContentBrief\Enums\GenerationStatus;
use Modules\ContentBrief\Models\ContentBrief;

class DeleteBriefAction
{
    use AsAction;

    /**
     * spec/ContentBrief_Technical_Specification.md §3.8 — bảo vệ output JSON đã tốn công sinh
     * ra khỏi bị xoá nhầm cùng brief cha. ArchiveBriefAction là lựa chọn thay thế phù hợp.
     */
    public function handle(ContentBrief $brief): void
    {
        throw_if(
            $brief->versions()->whereHas('generations', fn ($q) => $q->where('status', GenerationStatus::Completed))->exists(),
            ValidationException::withMessages([
                'brief' => 'Brief này đã có kết quả sinh nội dung hoàn tất — hãy Lưu trữ (archive) thay vì xoá.',
            ])
        );

        $brief->delete();
    }
}

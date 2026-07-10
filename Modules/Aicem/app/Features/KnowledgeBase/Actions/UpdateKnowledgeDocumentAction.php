<?php

namespace Modules\Aicem\Features\KnowledgeBase\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Aicem\Features\KnowledgeBase\Data\KnowledgeDocumentData;
use Modules\Aicem\Features\KnowledgeBase\Support\KnowledgeDocumentSlotGuard;
use Modules\Aicem\Models\AicemKnowledgeDocument;

/**
 * type/subject_type bất biến sau khi tạo (đổi "slot" thực chất là xoá + tạo mới) — chỉ
 * title/content/scope/scope_match/priority được sửa qua đây.
 *
 * Mỗi lần chạy → tạo version mới lưu lại trạng thái SẮP bị ghi đè, TRƯỚC khi update bản hiện
 * hành (spec/AICEM_Technical_Specification.md mục 5.2/10), giống pattern CreateConfigSnapshotAction
 * của Modules\Assessment.
 */
class UpdateKnowledgeDocumentAction
{
    use AsAction;

    public function handle(AicemKnowledgeDocument $document, KnowledgeDocumentData $data): AicemKnowledgeDocument
    {
        KnowledgeDocumentSlotGuard::assertValid($document->type, $document->subject_type, $data->scope);

        return DB::transaction(function () use ($document, $data) {
            $document->versions()->create([
                'organization_id' => $document->organization_id,
                'version'         => $document->current_version,
                'content'         => $document->content,
                'scope'           => $document->scope,
                'scope_match'     => $document->scope_match,
                'priority'        => $document->priority,
                'changed_by'      => auth()->id(),
                'changed_at'      => now(),
            ]);

            $document->update([
                'title'           => $data->title,
                'content'         => $data->content,
                'scope'           => $data->scope,
                'scope_match'     => $data->scope_match,
                'priority'        => $data->priority ?? $document->priority,
                'current_version' => $document->current_version + 1,
                'updated_by'      => auth()->id(),
            ]);

            return $document;
        });
    }
}

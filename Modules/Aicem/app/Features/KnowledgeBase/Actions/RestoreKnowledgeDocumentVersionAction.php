<?php

namespace Modules\Aicem\Features\KnowledgeBase\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Aicem\Models\AicemKnowledgeDocument;
use Modules\Aicem\Models\AicemKnowledgeDocumentVersion;

/**
 * Rollback về 1 version lịch sử — giống RestoreConfigFromSnapshotAction của Modules\Assessment:
 * KHÔNG reset current_version về số cũ, mà archive trạng thái hiện tại thành 1 version mới rồi
 * mới áp nội dung lịch sử vào bản hiện hành, để bản thân thao tác rollback cũng được audit lại
 * được (spec/AICEM_Technical_Specification.md mục 2, 10).
 */
class RestoreKnowledgeDocumentVersionAction
{
    use AsAction;

    public function handle(AicemKnowledgeDocument $document, AicemKnowledgeDocumentVersion $version): AicemKnowledgeDocument
    {
        if ($version->knowledge_document_id !== $document->id) {
            throw new \InvalidArgumentException('Version này không thuộc về knowledge document đang thao tác.');
        }

        return DB::transaction(function () use ($document, $version) {
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
                'content'         => $version->content,
                'scope'           => $version->scope,
                'scope_match'     => $version->scope_match,
                'priority'        => $version->priority,
                'current_version' => $document->current_version + 1,
                'updated_by'      => auth()->id(),
            ]);

            return $document;
        });
    }
}

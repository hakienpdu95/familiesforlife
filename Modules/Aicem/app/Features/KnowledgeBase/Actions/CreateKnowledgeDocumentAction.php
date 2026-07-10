<?php

namespace Modules\Aicem\Features\KnowledgeBase\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Aicem\Features\KnowledgeBase\Data\KnowledgeDocumentData;
use Modules\Aicem\Features\KnowledgeBase\Support\KnowledgeDocumentSlotGuard;
use Modules\Aicem\Models\AicemKnowledgeDocument;

class CreateKnowledgeDocumentAction
{
    use AsAction;

    public function handle(KnowledgeDocumentData $data): AicemKnowledgeDocument
    {
        KnowledgeDocumentSlotGuard::assertValid($data->type, $data->subject_type, $data->scope);

        // custom_note là slot thoát hiểm — priority mặc định cao hơn hẳn baseline (900 thay vì 100)
        // để luôn chèn SAU CÙNG và override theo quy tắc "đoạn xuất hiện sau thắng" (mục 6.3.1).
        $priority = $data->priority ?? ($data->type === 'custom_note' ? 900 : 100);

        return AicemKnowledgeDocument::create([
            'type'            => $data->type,
            'subject_type'    => $data->subject_type,
            'scope'           => $data->scope,
            'scope_match'     => $data->scope_match,
            'priority'        => $priority,
            'title'           => $data->title,
            'content'         => $data->content,
            'current_version' => 1,
            'created_by'      => auth()->id(),
            'updated_by'      => auth()->id(),
        ]);
    }
}

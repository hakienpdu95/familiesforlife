<?php

namespace Modules\Aicem\Features\ExampleLearning\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Aicem\Enums\ExampleCandidateStatus;
use Modules\Aicem\Features\KnowledgeBase\Actions\CreateKnowledgeDocumentAction;
use Modules\Aicem\Features\KnowledgeBase\Data\KnowledgeDocumentData;
use Modules\Aicem\Models\AicemExampleCandidate;

/**
 * Duyệt 1 candidate → tạo thật 1 aicem_knowledge_documents(type=example_good) — tái dùng
 * CreateKnowledgeDocumentAction (Phase 2) để đi qua đúng 1 đường validate/slot-guard duy nhất,
 * không tự viết logic tạo knowledge document riêng ở đây (mục 6.3.1).
 */
class ApproveExampleCandidateAction
{
    use AsAction;

    public function __construct(
        private readonly CreateKnowledgeDocumentAction $createDocument,
    ) {}

    public function handle(AicemExampleCandidate $candidate, int $userId): AicemExampleCandidate
    {
        if ($candidate->status !== ExampleCandidateStatus::Pending) {
            throw new \RuntimeException("Candidate đã được xử lý trước đó ({$candidate->status->value}).");
        }

        $document = $this->createDocument->handle(new KnowledgeDocumentData(
            type: 'example_good',
            title: $candidate->suggested_title,
            content: $candidate->suggested_content,
            subject_type: $candidate->subject_type,
            scope: $candidate->suggested_scope,
        ));

        $candidate->update([
            'status'                        => ExampleCandidateStatus::Approved,
            'reviewed_by'                   => $userId,
            'reviewed_at'                   => now(),
            'created_knowledge_document_id' => $document->id,
        ]);

        return $candidate;
    }
}

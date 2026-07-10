<?php

namespace Modules\Aicem\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Aicem\Enums\ExampleCandidateStatus;

/**
 * Hàng chờ duyệt thủ công (Phase 5, mục 11/15) — AI_Operator approve/reject trước khi nội dung
 * thật sự trở thành 1 aicem_knowledge_documents(type=example_good).
 */
class AicemExampleCandidate extends TenantAwareModel
{
    protected $table = 'aicem_example_candidates';

    protected $fillable = [
        'subject_type',
        'subject_id',
        'suggested_title',
        'suggested_content',
        'suggested_scope',
        'status',
        'reviewed_by',
        'reviewed_at',
        'created_knowledge_document_id',
    ];

    protected function casts(): array
    {
        return [
            'suggested_scope' => 'array',
            'status'          => ExampleCandidateStatus::class,
            'reviewed_at'     => 'datetime',
        ];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function knowledgeDocument(): BelongsTo
    {
        return $this->belongsTo(AicemKnowledgeDocument::class, 'created_knowledge_document_id');
    }

    /** Model của subject (hiện chỉ post_article) tra qua config('aicem_subjects') — không dùng morphTo() (mục 7). */
    public function subject(): ?\Illuminate\Database\Eloquent\Model
    {
        $modelClass = config("aicem_subjects.{$this->subject_type}.model");

        return $modelClass ? $modelClass::withoutGlobalScopes()->find($this->subject_id) : null;
    }
}

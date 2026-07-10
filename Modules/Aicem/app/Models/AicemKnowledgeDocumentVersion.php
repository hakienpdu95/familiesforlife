<?php

namespace Modules\Aicem\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Aicem\Enums\ScopeMatch;

/**
 * Không extends TenantAwareModel — luôn truy cập qua AicemKnowledgeDocument cha, không cần
 * global scope riêng, nhưng vẫn giữ organization_id để truy vết trực tiếp khi audit (mục 7).
 */
class AicemKnowledgeDocumentVersion extends Model
{
    protected $table = 'aicem_knowledge_document_versions';

    public $timestamps = false;

    protected $fillable = [
        'knowledge_document_id',
        'organization_id',
        'version',
        'content',
        'scope',
        'scope_match',
        'priority',
        'changed_by',
        'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'scope'       => 'array',
            'scope_match' => ScopeMatch::class,
            'priority'    => 'integer',
            'version'     => 'integer',
            'changed_at'  => 'datetime',
        ];
    }

    public function knowledgeDocument(): BelongsTo
    {
        return $this->belongsTo(AicemKnowledgeDocument::class, 'knowledge_document_id');
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}

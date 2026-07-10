<?php

namespace Modules\Aicem\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Aicem\Enums\ScopeMatch;

class AicemKnowledgeDocument extends TenantAwareModel
{
    protected $table = 'aicem_knowledge_documents';

    protected $fillable = [
        'type',
        'subject_type',
        'scope',
        'scope_match',
        'priority',
        'title',
        'content',
        'current_version',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'scope'           => 'array',
            'scope_match'     => ScopeMatch::class,
            'priority'        => 'integer',
            'current_version' => 'integer',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(AicemKnowledgeDocumentVersion::class, 'knowledge_document_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

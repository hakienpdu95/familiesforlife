<?php

namespace Modules\KcItem\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KcLearningProgress extends TenantAwareModel
{
    protected $table = 'kc_learning_progress';

    protected $fillable = [
        'organization_id',
        'user_id',
        'kc_item_id',
        'status',
        'started_at',
        'completed_at',
        'note',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kcItem(): BelongsTo
    {
        return $this->belongsTo(KcItem::class, 'kc_item_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}

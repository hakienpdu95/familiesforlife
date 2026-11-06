<?php

namespace Modules\Approval\Models;

use App\Foundation\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Approval\Enums\ApprovalStatus;

class ApprovalSubject extends BaseModel
{
    protected $table = 'approval_subjects';

    protected $fillable = [
        'subject_type',
        'subject_id',
        'status',
        'approved_by',
        'approved_at',
        'public_snapshot',
    ];

    protected $casts = [
        'status'          => ApprovalStatus::class,
        'approved_at'     => 'datetime',
        'public_snapshot' => 'array',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ApprovalLog::class);
    }
}

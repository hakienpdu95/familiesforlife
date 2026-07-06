<?php

namespace Modules\Sop\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SopRelation extends Model
{
    public $timestamps = false;

    protected $table = 'sop_relations';

    protected $fillable = [
        'uuid',
        'sop_id',
        'related_sop_id',
        'relation_type',
        'note',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function sop(): BelongsTo
    {
        return $this->belongsTo(SopProcess::class, 'sop_id');
    }

    public function relatedSop(): BelongsTo
    {
        return $this->belongsTo(SopProcess::class, 'related_sop_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

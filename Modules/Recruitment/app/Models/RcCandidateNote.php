<?php

namespace Modules\Recruitment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\Recruitment\Enums\NoteType;

class RcCandidateNote extends Model
{
    protected $table = 'rc_candidate_notes';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'candidate_id',
        'application_id',
        'content',
        'note_type',
        'is_private',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'note_type'  => NoteType::class,
        'is_private' => 'boolean',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
        });
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(RcCandidate::class, 'candidate_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(RcApplication::class, 'application_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

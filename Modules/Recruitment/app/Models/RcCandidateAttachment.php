<?php

namespace Modules\Recruitment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\Recruitment\Enums\AttachmentFileType;

class RcCandidateAttachment extends Model
{
    protected $table = 'rc_candidate_attachments';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'candidate_id',
        'application_id',
        'file_type',
        'file_name',
        'file_url',
        'file_size_kb',
        'storage_provider',
        'storage_key',
        'uploaded_by',
        'uploaded_at',
    ];

    protected $casts = [
        'file_type'   => AttachmentFileType::class,
        'uploaded_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->uploaded_at)) {
                $model->uploaded_at = now();
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

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function fileSizeFormatted(): string
    {
        if ($this->file_size_kb >= 1024) {
            return round($this->file_size_kb / 1024, 1) . ' MB';
        }
        return $this->file_size_kb . ' KB';
    }
}

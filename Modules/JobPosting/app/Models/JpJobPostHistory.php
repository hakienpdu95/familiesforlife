<?php

namespace Modules\JobPosting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\JobPosting\Enums\HistoryChangeType;
use Modules\JobPosting\Enums\JobPostStatus;

class JpJobPostHistory extends Model
{
    protected $table = 'jp_job_post_histories';

    public const UPDATED_AT = null;

    protected $fillable = [
        'uuid',
        'job_post_id',
        'change_type',
        'old_status',
        'new_status',
        'changed_fields',
        'note',
        'changed_by',
    ];

    protected $casts = [
        'change_type' => HistoryChangeType::class,
        'old_status'  => JobPostStatus::class,
        'new_status'  => JobPostStatus::class,
        'created_at'  => 'datetime',
    ];

    public function jobPost(): BelongsTo
    {
        return $this->belongsTo(JpJobPost::class, 'job_post_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}

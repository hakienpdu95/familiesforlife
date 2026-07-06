<?php

namespace Modules\Task\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class TaskComment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'task_id',
        'user_id',
        'parent_id',
        'content',
        'is_edited',
    ];

    protected $casts = [
        'is_edited' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (TaskComment $comment) {
            if (empty($comment->uuid)) {
                $comment->uuid = (string) Str::uuid();
            }
        });
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(TaskComment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TaskComment::class, 'parent_id');
    }

    public function mentions(): HasMany
    {
        return $this->hasMany(TaskCommentMention::class, 'comment_id');
    }
}

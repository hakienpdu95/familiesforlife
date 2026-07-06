<?php

namespace Modules\Task\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskCommentMention extends Model
{
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = ['comment_id', 'user_id', 'created_at'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(TaskComment::class, 'comment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

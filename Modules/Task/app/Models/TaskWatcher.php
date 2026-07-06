<?php

namespace Modules\Task\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskWatcher extends Model
{
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = ['task_id', 'user_id', 'watched_at'];

    protected $casts = [
        'watched_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

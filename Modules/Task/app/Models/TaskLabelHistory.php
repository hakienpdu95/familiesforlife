<?php

namespace Modules\Task\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskLabelHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'task_id',
        'label_id',
        'actor_id',
        'action',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function label(): BelongsTo
    {
        return $this->belongsTo(TaskLabel::class, 'label_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}

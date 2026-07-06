<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PassportSandboxSummary extends Model
{
    protected $table = 'passport_sandbox_summaries';

    public $timestamps = false;

    protected $fillable = [
        'passport_entry_id',
        'sandbox_env_id',
        'env_code',
        'env_name',
        'sessions_completed',
        'hours_spent',
        'avg_score',
    ];

    protected function casts(): array
    {
        return [
            'hours_spent' => 'float',
            'avg_score'   => 'float',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(PassportEntry::class, 'passport_entry_id');
    }
}

<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignSandboxTask extends Model
{
    protected $table = 'campaign_sandbox_tasks';

    public $timestamps = false;

    protected $fillable = [
        'campaign_id',
        'sandbox_task_id',
        'is_required',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(OpenAssessmentCampaign::class, 'campaign_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(SandboxTask::class, 'sandbox_task_id');
    }
}

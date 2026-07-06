<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignParticipationScore extends Model
{
    protected $table = 'campaign_participation_scores';

    public $timestamps = false;

    protected $fillable = [
        'participation_id',
        'domain_code',
        'score',
    ];

    protected function casts(): array
    {
        return ['score' => 'float'];
    }

    public function participation(): BelongsTo
    {
        return $this->belongsTo(CampaignParticipation::class, 'participation_id');
    }
}

<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignDomainRequirement extends Model
{
    protected $table = 'campaign_domain_requirements';

    public $timestamps = false;

    protected $fillable = [
        'campaign_id',
        'domain_code',
        'min_score',
        'is_required',
    ];

    protected function casts(): array
    {
        return [
            'min_score'   => 'float',
            'is_required' => 'boolean',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(OpenAssessmentCampaign::class, 'campaign_id');
    }
}

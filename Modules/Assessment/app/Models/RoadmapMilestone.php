<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\KcItem\Models\KcItem;

class RoadmapMilestone extends Model
{
    protected $table = 'roadmap_milestones';

    protected $fillable = [
        'phase_id',
        'title',
        'description',
        'sort_order',
    ];

    public function phase(): BelongsTo
    {
        return $this->belongsTo(RoadmapPhase::class, 'phase_id');
    }

    public function kcItems(): BelongsToMany
    {
        return $this->belongsToMany(KcItem::class, 'roadmap_milestone_kc_items', 'roadmap_milestone_id', 'kc_item_id')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order')
            ->withTimestamps();
    }
}

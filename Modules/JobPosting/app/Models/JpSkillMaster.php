<?php

namespace Modules\JobPosting\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JpSkillMaster extends TenantAwareModel
{
    protected $table = 'jp_skill_masters';

    protected $fillable = [
        'uuid',
        'organization_id',
        'name',
        'slug',
        'category',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function jobPostSkills(): HasMany
    {
        return $this->hasMany(JpJobPostSkill::class, 'skill_id');
    }
}

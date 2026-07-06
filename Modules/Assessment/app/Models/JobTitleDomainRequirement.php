<?php

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Model;

class JobTitleDomainRequirement extends Model
{
    protected $table = 'job_title_domain_requirements';

    protected $fillable = [
        'organization_id',
        'job_title_id',
        'domain_code',
        'required_score',
        'is_critical',
        'notes',
    ];

    protected $casts = [
        'required_score' => 'float',
        'is_critical'    => 'boolean',
    ];

    /**
     * Get required domain scores for a given job title, keyed by domain_code.
     *
     * Tries org-specific rows first (organization_id = $orgId), falls back to
     * system rows (organization_id = null). Returns e.g. ['D1' => 3.5, 'D2' => 4.0, ...]
     */
    public static function getForJobTitle(?int $jobTitleId, ?int $orgId): array
    {
        if ($jobTitleId === null) {
            return [];
        }

        $rows = static::where('job_title_id', $jobTitleId)
            ->where(function ($q) use ($orgId) {
                $q->where('organization_id', $orgId)
                  ->orWhereNull('organization_id');
            })
            ->orderByDesc('organization_id') // org-specific (non-null) wins over null
            ->get();

        $result = [];
        foreach ($rows as $row) {
            // First occurrence wins because rows are ordered org-specific first
            if (! isset($result[$row->domain_code])) {
                $result[$row->domain_code] = $row->required_score;
            }
        }

        return $result;
    }
}

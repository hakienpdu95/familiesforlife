<?php

namespace Modules\Lead\Listeners;

use Modules\Assessment\Actions\RunAssessmentAction;
use Modules\Assessment\Models\AssessmentResult;
use Modules\Lead\Events\LeadCreated;
use Modules\Lead\Events\LeadUpdated;
use Modules\Lead\Models\Lead;

class TriggerLeadAssessment
{
    // Fields that affect scoring — re-score only when these change
    private const SCORING_FIELDS = [
        'contact_phone', 'contact_company', 'title', 'description',
        'expected_value', 'expected_close_date', 'assigned_to',
        'pipeline_stage_id', 'lead_score', 'activity_count',
    ];

    // Minimum seconds between re-scores for the same lead
    private const COOLDOWN_SECONDS = 3600; // 1 hour

    public function handle(LeadCreated|LeadUpdated $event): void
    {
        $lead = $event->lead;

        if (!$lead->getAssessmentCode()) {
            return;
        }

        if ($event instanceof LeadUpdated && !$this->hasScoringFieldChanged($lead)) {
            return;
        }

        if ($this->isInCooldown($lead)) {
            return;
        }

        RunAssessmentAction::dispatch($lead);
    }

    private function hasScoringFieldChanged(Lead $lead): bool
    {
        // getDirty() is empty after model is already saved via observer/event
        // so we check wasChanged() which reflects the last save
        foreach (self::SCORING_FIELDS as $field) {
            if ($lead->wasChanged($field)) {
                return true;
            }
        }
        return false;
    }

    private function isInCooldown(Lead $lead): bool
    {
        $latest = AssessmentResult::where('subject_type', Lead::class)
            ->where('subject_id', $lead->id)
            ->value('calculated_at');

        if (!$latest) {
            return false;
        }

        return now()->diffInSeconds($latest) < self::COOLDOWN_SECONDS;
    }
}

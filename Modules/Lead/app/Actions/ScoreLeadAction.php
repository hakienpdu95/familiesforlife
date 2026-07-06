<?php

namespace Modules\Lead\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Lead\Models\Lead;

class ScoreLeadAction
{
    use AsAction;

    public string $jobQueue = 'default';

    public function __construct()
    {
        $this->jobQueue = config('lead.queue', 'default');
    }

    public function handle(int $leadId): void
    {
        $lead = Lead::withoutGlobalScopes()->with('stage:id,probability')->findOrFail($leadId);

        $score = 0;

        // Max 40pts from survey score (maps 0–100 survey → 0–40 lead score)
        if ($lead->survey_score !== null) {
            $score += min(40, (int) ($lead->survey_score * 0.4));
        }

        // Deal size
        if ($lead->expected_value > 100_000_000) $score += 20; // > 100M VND
        elseif ($lead->expected_value > 10_000_000) $score += 10; // > 10M VND

        // Contact completeness
        if ($lead->contact_phone)   $score += 10;
        if ($lead->contact_company) $score += 5;

        // Assigned
        if ($lead->assigned_to) $score += 5;

        // Pipeline progress
        if ($lead->stage?->probability > 50) $score += 10;
        if ($lead->stage?->probability > 75) $score += 5; // bonus for near-close
        if ($lead->stage?->probability === 100) $score += 5; // won stage

        $lead->update([
            'lead_score'       => min(100, $score),
            'score_updated_at' => now(),
        ]);
    }

    // Called after CreateLeadAction — runs async on the leads queue
    public static function dispatchForLead(int $leadId): void
    {
        static::dispatch($leadId)->onQueue(config('lead.queue', 'default'));
    }
}

<?php

namespace Modules\Assessment\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Modules\Assessment\Enums\ParticipationStatus;
use Modules\Assessment\Models\CampaignParticipation;
use Modules\Assessment\Models\OpenAssessmentCampaign;
use Modules\Assessment\Models\PassportDomainScore;
use Modules\Assessment\Models\PassportEntry;

class CreateCampaignPassportEntryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 30;

    public function __construct(
        private readonly int $participationId,
    ) {}

    public function handle(): void
    {
        $participation = CampaignParticipation::with(['campaign.organization', 'scores'])->find($this->participationId);

        if (!$participation || !$participation->isCompleted()) {
            return;
        }

        // Idempotent — already linked
        if ($participation->passport_entry_id) {
            return;
        }

        $campaign = $participation->campaign;

        DB::transaction(function () use ($participation, $campaign) {
            $entry = PassportEntry::create([
                'user_id'              => $participation->user_id,
                'entry_type'           => 'campaign_result',
                'source_org_id'        => $campaign->organization_id,
                'source_org_name'      => $campaign->organization?->name,
                'snapshot_at'          => $participation->completed_at ?? now(),
                'tdwcf_score'          => $participation->result_tdwcf_score,
                'tdwcf_maturity_level' => $participation->result_maturity_level,
                'sandbox_score_avg'    => $participation->result_sandbox_avg,
                'visibility'           => 'private',
            ]);

            $domainNames = [
                'D1' => 'Năng lực số cơ bản',
                'D2' => 'Năng lực dữ liệu',
                'D3' => 'Năng lực AI',
                'D4' => 'Quy trình & Tự động hoá',
                'D5' => 'Đổi mới & Sáng kiến',
                'D6' => 'Hiệu suất & KPI',
            ];

            // Domain scores snapshot
            foreach ($participation->scores as $s) {
                PassportDomainScore::create([
                    'passport_entry_id' => $entry->id,
                    'domain_code'       => $s->domain_code,
                    'domain_name'       => $domainNames[$s->domain_code] ?? $s->domain_code,
                    'score'             => $s->score,
                ]);
            }

            // Link passport entry back to participation
            CampaignParticipation::where('id', $participation->id)
                ->update(['passport_entry_id' => $entry->id]);

            // Increment org campaign completed counter
            OpenAssessmentCampaign::where('id', $campaign->id)
                ->increment('completed_count');
        });
    }
}

<?php

namespace Modules\Assessment\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Assessment\Enums\CampaignStatus;
use Modules\Assessment\Enums\ParticipationStatus;
use Modules\Assessment\Models\OpenAssessmentCampaign;
use Modules\Assessment\Notifications\CampaignReminderNotification;

class SendCampaignReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        OpenAssessmentCampaign::where('status', CampaignStatus::Open->value)
            ->whereNotNull('open_until')
            ->whereBetween('open_until', [now(), now()->addDays(3)])
            ->each(function (OpenAssessmentCampaign $campaign) {
                $campaign->participations()
                    ->where('status', ParticipationStatus::InProgress->value)
                    ->with('user')
                    ->each(fn($p) => $p->user->notify(
                        new CampaignReminderNotification($campaign, $p)
                    ));
            });
    }
}

<?php

namespace Modules\Marketplace\Observers;

use Modules\JobPosting\Models\JpJobPost;
use Modules\Marketplace\Enums\ExperienceLevel;
use Modules\Marketplace\Enums\JpSyncStatus;
use Modules\Marketplace\Enums\ListingStatus;
use Modules\Marketplace\Enums\ListingType;
use Modules\Marketplace\Enums\ListingVisibility;
use Modules\Marketplace\Enums\PosterType;
use Modules\Marketplace\Enums\WorkType;
use Modules\Marketplace\Models\MktListing;

class JpJobPostObserver
{
    public function updated(JpJobPost $jpPost): void
    {
        if ($jpPost->wasChanged('status')) {
            $this->handleStatusChange($jpPost);
            return;  // status change is handled separately, not out_of_sync
        }

        // Content changed after publish → mark out_of_sync
        if ($jpPost->status?->value === 'published') {
            MktListing::withoutTenant()
                ->where('jp_job_post_id', $jpPost->uuid)
                ->whereNotIn('status', [\Modules\Marketplace\Enums\ListingStatus::CLOSED->value])
                ->update(['jp_sync_status' => \Modules\Marketplace\Enums\JpSyncStatus::OUT_OF_SYNC->value]);
        }
    }

    private function handleStatusChange(JpJobPost $jpPost): void
    {
        $newStatus = $jpPost->status?->value ?? $jpPost->status;

        if ($newStatus === 'published' && $jpPost->publish_to_marketplace) {
            $this->syncToMarketplace($jpPost);
            return;
        }

        // Auto-close marketplace listing when JP closes
        if (in_array($newStatus, ['closed', 'archived'], true)) {
            MktListing::withoutTenant()
                ->where('jp_job_post_id', $jpPost->uuid)
                ->where('auto_close_on_jp', true)
                ->whereNotIn('status', [ListingStatus::CLOSED->value])
                ->update([
                    'status'    => ListingStatus::CLOSED->value,
                    'closed_at' => now(),
                ]);
        }
    }

    private function syncToMarketplace(JpJobPost $jpPost): void
    {
        $existing = MktListing::withoutTenant()
            ->where('jp_job_post_id', $jpPost->uuid)
            ->whereNotIn('status', [ListingStatus::CLOSED->value])
            ->first();

        $data = [
            'org_id'           => $jpPost->org_id,
            'posted_by'        => $jpPost->created_by ?? $jpPost->updated_by ?? 1,
            'poster_type'      => PosterType::ORG->value,
            'listing_type'     => ListingType::JOB->value,
            'title'            => $jpPost->title,
            'description'      => $jpPost->description ?? '',
            'requirements'     => $jpPost->requirements,
            'status'           => ListingStatus::ACTIVE->value,
            'visibility'       => ListingVisibility::PUBLIC->value,
            'work_type'        => $this->mapWorkType($jpPost->work_arrangement),
            'experience_level' => $this->mapExperienceLevel($jpPost->experience_level),
            'employment_type'  => $jpPost->employment_type,
            'headcount'        => $jpPost->headcount ?? 1,
            'location'         => $jpPost->location,
            'expire_at'        => $jpPost->expire_at,
            'jp_job_post_id'   => $jpPost->uuid,
            'jp_sync_status'   => JpSyncStatus::SYNCED->value,
            'auto_close_on_jp' => true,
        ];

        if ($existing) {
            $existing->update(array_merge($data, ['jp_sync_status' => JpSyncStatus::SYNCED->value]));
        } else {
            MktListing::create($data);
        }
    }

    private function mapWorkType(?string $arrangement): string
    {
        return match ($arrangement) {
            'remote'  => WorkType::REMOTE->value,
            'onsite'  => WorkType::ONSITE->value,
            'hybrid'  => WorkType::HYBRID->value,
            default   => WorkType::FLEXIBLE->value,
        };
    }

    private function mapExperienceLevel(?string $level): string
    {
        return match ($level) {
            'entry'  => ExperienceLevel::ENTRY->value,
            'junior' => ExperienceLevel::JUNIOR->value,
            'mid'    => ExperienceLevel::MID->value,
            'senior' => ExperienceLevel::SENIOR->value,
            'lead'   => ExperienceLevel::LEAD->value,
            default  => ExperienceLevel::ANY->value,
        };
    }
}

<?php

namespace Modules\JobPosting\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Modules\JobPosting\Enums\HistoryChangeType;
use Modules\JobPosting\Enums\JobPostStatus;
use Modules\JobPosting\Models\JpJobPost;
use Modules\JobPosting\Models\JpJobPostHistory;
use Modules\JobPosting\Models\MktListing;

class ExpireJpJobPostsCommand extends Command
{
    protected $signature = 'jp:expire-posts';

    protected $description = 'Tự động đóng các tin tuyển dụng đã hết hạn (expire_at < now AND status = published).';

    public function handle(): int
    {
        $expired = JpJobPost::withoutTenant()
            ->where('status', JobPostStatus::Published->value)
            ->whereNotNull('expire_at')
            ->where('expire_at', '<', now())
            ->get();

        if ($expired->isEmpty()) {
            $this->info('Không có tin tuyển dụng nào hết hạn.');
            return self::SUCCESS;
        }

        foreach ($expired as $post) {
            $oldStatus = $post->status;

            JpJobPost::withoutTenant()
                ->where('id', $post->id)
                ->update([
                    'status'    => JobPostStatus::Closed->value,
                    'closed_at' => now(),
                ]);

            JpJobPostHistory::create([
                'uuid'        => Str::uuid()->toString(),
                'job_post_id' => $post->id,
                'change_type' => HistoryChangeType::Closed->value,
                'old_status'  => $oldStatus?->value,
                'new_status'  => JobPostStatus::Closed->value,
                'note'        => 'Auto-closed: expired',
                'changed_by'  => $post->owner_id,
            ]);

            if ($post->mkt_listing_id) {
                MktListing::where('uuid', $post->mkt_listing_id)->update([
                    'status'    => 'closed',
                    'closed_at' => now(),
                ]);
            }

            $this->line("  [closed] [{$post->code}] {$post->title} (org: {$post->organization_id})");
        }

        $this->info("Đã đóng {$expired->count()} tin tuyển dụng hết hạn.");

        return self::SUCCESS;
    }
}

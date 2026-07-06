<?php

namespace Modules\JobPosting\Actions\Backend;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\JobPosting\Enums\HistoryChangeType;
use Modules\JobPosting\Enums\JobPostStatus;
use Modules\JobPosting\Events\JpJobPostStatusChanged;
use Modules\JobPosting\Models\JpJobPost;
use Modules\JobPosting\Models\JpJobPostHistory;

class PublishJpJobPostAction
{
    use AsAction;

    public function handle(JpJobPost $post): JpJobPost
    {
        $oldStatus = $post->status;

        $post->update([
            'status'       => JobPostStatus::Published->value,
            'published_at' => now(),
            'reviewed_by'  => Auth::id(),
            'updated_by'   => Auth::id(),
        ]);

        JpJobPostHistory::create([
            'uuid'        => Str::uuid()->toString(),
            'job_post_id' => $post->id,
            'change_type' => HistoryChangeType::Published->value,
            'old_status'  => $oldStatus?->value,
            'new_status'  => JobPostStatus::Published->value,
            'changed_by'  => Auth::id(),
        ]);

        event(new JpJobPostStatusChanged($post, $oldStatus, JobPostStatus::Published));

        return $post->fresh();
    }
}

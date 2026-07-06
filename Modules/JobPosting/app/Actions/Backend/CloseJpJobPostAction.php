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

class CloseJpJobPostAction
{
    use AsAction;

    public function handle(JpJobPost $post, ?string $note = null): JpJobPost
    {
        $oldStatus = $post->status;

        $post->update([
            'status'     => JobPostStatus::Closed->value,
            'closed_at'  => now(),
            'updated_by' => Auth::id(),
        ]);

        JpJobPostHistory::create([
            'uuid'        => Str::uuid()->toString(),
            'job_post_id' => $post->id,
            'change_type' => HistoryChangeType::Closed->value,
            'old_status'  => $oldStatus?->value,
            'new_status'  => JobPostStatus::Closed->value,
            'note'        => $note,
            'changed_by'  => Auth::id(),
        ]);

        event(new JpJobPostStatusChanged($post, $oldStatus, JobPostStatus::Closed));

        return $post->fresh();
    }
}

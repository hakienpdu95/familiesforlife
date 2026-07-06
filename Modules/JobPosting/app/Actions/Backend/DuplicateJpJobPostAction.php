<?php

namespace Modules\JobPosting\Actions\Backend;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\JobPosting\Enums\HistoryChangeType;
use Modules\JobPosting\Enums\JobPostStatus;
use Modules\JobPosting\Models\JpJobPost;
use Modules\JobPosting\Models\JpJobPostHistory;

class DuplicateJpJobPostAction
{
    use AsAction;

    public function handle(JpJobPost $source): JpJobPost
    {
        $userId = Auth::id();
        $orgId  = $source->organization_id;

        // Generate unique code: JP-YYYY-NNN
        $year   = now()->year;
        $prefix = "JP-{$year}-";
        $last   = JpJobPost::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('code', 'like', "{$prefix}%")
            ->max('code');
        $seq  = $last ? ((int) substr($last, strlen($prefix)) + 1) : 1;
        $code = $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);

        // Generate unique slug
        $baseSlug = Str::slug($source->title) . '-copy';
        $slug     = $baseSlug;
        $n        = 2;
        while (JpJobPost::withoutTenant()->where('organization_id', $orgId)->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$n}";
            $n++;
        }

        $newPost = $source->replicate([
            'uuid', 'code', 'slug', 'status',
            'published_at', 'closed_at', 'expire_at',
            'hired_count', 'view_count', 'application_count', 'share_count',
            'mkt_listing_id', 'mkt_sync_status',
            'created_by', 'owner_id', 'reviewed_by', 'updated_by',
        ]);

        $newPost->fill([
            'uuid'         => Str::uuid()->toString(),
            'code'         => $code,
            'slug'         => $slug,
            'status'       => JobPostStatus::Draft->value,
            'published_at' => null,
            'closed_at'    => null,
            'expire_at'    => null,
            'hired_count'  => 0,
            'view_count'   => 0,
            'application_count' => 0,
            'share_count'  => 0,
            'mkt_listing_id'  => null,
            'mkt_sync_status' => null,
            'created_by'   => $userId,
            'owner_id'     => $source->owner_id,
            'reviewed_by'  => null,
            'updated_by'   => null,
        ]);

        $newPost->save();

        // Duplicate skills
        foreach ($source->skills as $skill) {
            $newPost->skills()->create([
                'uuid'              => Str::uuid()->toString(),
                'skill_id'          => $skill->skill_id,
                'skill_name'        => $skill->skill_name,
                'requirement_level' => $skill->requirement_level,
                'proficiency'       => $skill->proficiency,
                'min_years'         => $skill->min_years,
                'sort_order'        => $skill->sort_order,
            ]);
        }

        // Duplicate benefits
        foreach ($source->benefits as $benefit) {
            $newPost->benefits()->create([
                'uuid'         => Str::uuid()->toString(),
                'benefit_id'   => $benefit->benefit_id,
                'benefit_name' => $benefit->benefit_name,
                'description'  => $benefit->description,
                'sort_order'   => $benefit->sort_order,
            ]);
        }

        // Duplicate screening questions + choices
        foreach ($source->screeningQuestions as $question) {
            $newQ = $newPost->screeningQuestions()->create([
                'uuid'                => Str::uuid()->toString(),
                'question_text'       => $question->question_text,
                'question_type'       => $question->question_type,
                'is_required'         => $question->is_required,
                'is_disqualifying'    => $question->is_disqualifying,
                'disqualify_if_answer'=> $question->disqualify_if_answer,
                'placeholder'         => $question->placeholder,
                'max_length'          => $question->max_length,
                'sort_order'          => $question->sort_order,
            ]);
            foreach ($question->choices as $choice) {
                $newQ->choices()->create([
                    'uuid'             => Str::uuid()->toString(),
                    'choice_text'      => $choice->choice_text,
                    'is_disqualifying' => $choice->is_disqualifying,
                    'sort_order'       => $choice->sort_order,
                ]);
            }
        }

        JpJobPostHistory::create([
            'uuid'        => Str::uuid()->toString(),
            'job_post_id' => $newPost->id,
            'change_type' => HistoryChangeType::Created->value,
            'new_status'  => JobPostStatus::Draft->value,
            'note'        => "Nhân bản từ #{$source->code}",
            'changed_by'  => $userId,
        ]);

        return $newPost;
    }
}

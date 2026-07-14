<?php

namespace Modules\Event\Features\PublicSubmission\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Event\Enums\EventStatus;
use Modules\Event\Features\EventModeration\Actions\BuildEventAttributesAction;
use Modules\Event\Features\EventModeration\Actions\NotifyEditorsOfNewSubmissionAction;
use Modules\Event\Features\EventModeration\Data\EventData;
use Modules\Event\Features\PublicSubmission\Data\EventSubmitterData;
use Modules\Event\Models\Event;
use Modules\Event\Models\EventSubmission;

/**
 * Độc giả ẩn danh nộp sự kiện qua form công khai (spec §5.3/§10.7). Khác CreateEventAction
 * (staff dashboard): tạo THÊM 1 dòng EventSubmission (source=public_form, PII người nộp), và
 * áp giới hạn chống spam §10.7 TRƯỚC khi tạo record (từ chối rõ ràng, không âm thầm queue rồi
 * reject sau).
 */
class SubmitEventAction
{
    use AsAction;

    public function __construct(private readonly BuildEventAttributesAction $buildAttributes) {}

    public function handle(
        EventData $data,
        EventSubmitterData $submitter,
        string $ipAddress,
        ?string $userAgent,
    ): Event {
        $this->guardAgainstAbuse($submitter->email);

        return DB::transaction(function () use ($data, $submitter, $ipAddress, $userAgent) {
            $event = Event::create([
                ...$this->buildAttributes->handle($data),
                'slug'        => $this->uniqueSlug($data->title),
                'poster_path' => $data->poster_path,
                'status'      => EventStatus::Submitted,
            ]);

            EventSubmission::create([
                'event_id'             => $event->id,
                'submitter_first_name' => $submitter->first_name,
                'submitter_last_name'  => $submitter->last_name,
                'submitter_email'      => $submitter->email,
                'newsletter_consent'   => $submitter->newsletter_consent,
                'consented_at'         => $submitter->newsletter_consent ? now() : null,
                'source'               => 'public_form',
                'ip_address'           => $ipAddress,
                'user_agent'           => $userAgent,
                'turnstile_verified'   => true, // đã qua ValidateEventTurnstile trước khi tới đây
            ]);

            NotifyEditorsOfNewSubmissionAction::run($event);

            return $event;
        });
    }

    /**
     * spec §10.7 — tối đa 3 sự kiện Published + chưa kết thúc / email / cửa sổ 90 ngày. Chặn
     * TRƯỚC khi tạo record — từ chối rõ ràng ngay tại đây, không âm thầm đưa vào hàng chờ rồi
     * reject sau.
     */
    private function guardAgainstAbuse(string $email): void
    {
        $activeCount = EventSubmission::where('submitter_email', $email)
            ->where('created_at', '>=', now()->subDays(90))
            ->whereHas('event', fn ($q) => $q->where('status', EventStatus::Published)->where('end_date', '>=', now()->toDateString()))
            ->count();

        abort_if(
            $activeCount >= 3,
            422,
            'Bạn đã có 3 sự kiện đang hiển thị công khai. Vui lòng chờ sự kiện cũ kết thúc trước khi nộp thêm.'
        );
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i    = 2;

        while (Event::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}

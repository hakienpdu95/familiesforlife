<?php

namespace Modules\Newsletter\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Newsletter\Models\NewsletterSubscriber;
use Resend\Laravel\Facades\Resend;

/**
 * spec/Newsletter_Technical_Specification.md §9.1/§0 mục 12 — gọi Resend Contact API bất đồng
 * bộ, không đồng bộ ngay trong request (giới hạn 10 request/giây/team của Resend, §2.2).
 * Không catch lỗi — job fail tự nhiên và tự retry theo $tries/$backoff.
 */
class SyncSubscriberToResendJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [10, 30, 60, 300, 900];

    public function __construct(
        private readonly int $subscriberId,
        private readonly string $fullName,
        private readonly string $email,
    ) {}

    /** §0 mục 9 — nguyên cụm full_name vào first_name, không tự tách. */
    public function handle(): void
    {
        $subscriber = NewsletterSubscriber::find($this->subscriberId);

        if (! $subscriber) {
            return; // đã bị xoá giữa lúc job chờ hàng đợi — không còn gì để đồng bộ
        }

        $segmentId = config('newsletter.resend_segment_id');
        $attrs     = ['first_name' => $this->fullName, 'unsubscribed' => false];

        if ($subscriber->resend_contact_id) {
            Resend::contacts()->update($subscriber->resend_contact_id, $attrs);

            return;
        }

        $contact = Resend::contacts()->create([...$attrs, 'email' => $this->email, 'segments' => [$segmentId]]);
        $subscriber->update(['resend_contact_id' => $contact->id]);
    }
}

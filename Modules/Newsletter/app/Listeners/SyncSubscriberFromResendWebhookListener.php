<?php

namespace Modules\Newsletter\Listeners;

use Modules\Newsletter\Enums\SubscriberStatus;
use Modules\Newsletter\Models\NewsletterSubscriber;
use Resend\Laravel\Events\ContactDeleted;
use Resend\Laravel\Events\ContactUpdated;
use Resend\Laravel\Events\EmailBounced;
use Resend\Laravel\Events\EmailComplained;

/**
 * spec/Newsletter_Technical_Specification.md §9.3/§0 mục 6 — lắng nghe Laravel Event có sẵn từ
 * package resend/resend-laravel (route webhook + xác thực chữ ký đã có sẵn trong package).
 *
 * ⚠ Cấu trúc payload['data'] dựa theo format chuẩn tài liệu Resend, BẮT BUỘC verify lại bằng
 * webhook test thật của Resend Dashboard trước khi coi là chốt (§14 Phase 4).
 */
class SyncSubscriberFromResendWebhookListener
{
    public function handleContactUpdated(ContactUpdated $event): void
    {
        $data = $event->payload['data'] ?? [];
        $subscriber = NewsletterSubscriber::where('resend_contact_id', $data['id'] ?? null)->first();

        if ($subscriber && ($data['unsubscribed'] ?? false)) {
            $subscriber->update(['status' => SubscriberStatus::Unsubscribed, 'unsubscribed_at' => now()]);
        }
    }

    public function handleContactDeleted(ContactDeleted $event): void
    {
        $data = $event->payload['data'] ?? [];

        NewsletterSubscriber::where('resend_contact_id', $data['id'] ?? null)
            ->update(['status' => SubscriberStatus::Unsubscribed]);
    }

    public function handleEmailBounced(EmailBounced $event): void
    {
        $email = $event->payload['data']['to'][0] ?? null;

        if ($email) {
            NewsletterSubscriber::where('email', $email)->update(['status' => SubscriberStatus::Bounced]);
        }
    }

    public function handleEmailComplained(EmailComplained $event): void
    {
        $email = $event->payload['data']['to'][0] ?? null;

        if ($email) {
            NewsletterSubscriber::where('email', $email)->update(['status' => SubscriberStatus::Complained]);
        }
    }
}

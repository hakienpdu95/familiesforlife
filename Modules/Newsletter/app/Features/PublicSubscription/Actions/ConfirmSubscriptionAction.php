<?php

namespace Modules\Newsletter\Features\PublicSubscription\Actions;

use Illuminate\Support\Facades\Mail;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Newsletter\Enums\SubscriberStatus;
use Modules\Newsletter\Jobs\SyncSubscriberToResendJob;
use Modules\Newsletter\Mail\WelcomeSubscriberMail;
use Modules\Newsletter\Models\NewsletterSubscriber;

/**
 * spec/Newsletter_Technical_Specification.md §9.1 — chỉ có ý nghĩa khi NEWSLETTER_DOUBLE_OPT_IN
 * =true (§0 mục 14). Người nhận bấm link ký chữ ký trong ConfirmSubscriptionMail.
 */
class ConfirmSubscriptionAction
{
    use AsAction;

    public function handle(NewsletterSubscriber $subscriber): NewsletterSubscriber
    {
        if ($subscriber->status !== SubscriberStatus::PendingConfirmation) {
            return $subscriber; // đã xác nhận rồi (bấm lại link cũ) hoặc đã unsubscribe — không làm gì thêm
        }

        $subscriber->update(['status' => SubscriberStatus::Active, 'confirmed_at' => now()]);

        SyncSubscriberToResendJob::dispatch($subscriber->id, $subscriber->full_name, $subscriber->email);
        Mail::to($subscriber->email)->queue(new WelcomeSubscriberMail($subscriber));

        return $subscriber;
    }
}

<?php

namespace Modules\Newsletter\Features\SubscriberManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Newsletter\Enums\SubscriberStatus;
use Modules\Newsletter\Jobs\UnsubscribeSubscriberFromResendJob;
use Modules\Newsletter\Models\NewsletterSubscriber;

/** spec/Newsletter_Technical_Specification.md §9.5/§0 mục 15 — xoá thủ công (admin). */
class RemoveSubscriberAction
{
    use AsAction;

    public function handle(NewsletterSubscriber $subscriber): void
    {
        // §0 mục 16 — bất kể subscriber đang ở status nào (kể cả `pending_confirmation`), luôn
        // set unsubscribed_at + soft-delete. Guard resend_contact_id TỰ NHIÊN xử lý đúng trường
        // hợp pending_confirmation: subscriber đó CHƯA TỪNG được SubscribeAction dispatch
        // SyncSubscriberToResendJob (nhánh double opt-in return sớm trước khi dispatch), nên
        // resend_contact_id chắc chắn vẫn null → job KHÔNG được gọi, đúng vì không có gì để gọi.
        if ($subscriber->resend_contact_id) {
            UnsubscribeSubscriberFromResendJob::dispatch($subscriber->resend_contact_id);
        }

        // KHÔNG set resend_contact_id = null ở đây (§0 mục 16) — cố tình GIỮ NGUYÊN cột này dù
        // đã soft-delete, để nếu người này đăng ký lại sau (SubscribeAction), job đồng bộ nhận
        // ra contact cũ và UPDATE lại thay vì CREATE ra 1 contact trùng cho cùng 1 email.
        $subscriber->update(['status' => SubscriberStatus::Unsubscribed, 'unsubscribed_at' => now()]);
        $subscriber->delete(); // soft-delete — KHÔNG forceDelete, giữ lịch sử (§0 mục 15)
    }
}

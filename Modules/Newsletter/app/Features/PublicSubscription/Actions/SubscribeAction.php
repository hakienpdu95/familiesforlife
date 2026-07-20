<?php

namespace Modules\Newsletter\Features\PublicSubscription\Actions;

use Illuminate\Support\Facades\Mail;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Newsletter\Enums\SubscriberStatus;
use Modules\Newsletter\Jobs\SyncSubscriberToResendJob;
use Modules\Newsletter\Mail\ConfirmSubscriptionMail;
use Modules\Newsletter\Mail\WelcomeSubscriberMail;
use Modules\Newsletter\Models\NewsletterSubscriber;

/**
 * spec/Newsletter_Technical_Specification.md §9.1 — thu thập, lưu DB, dispatch đồng bộ (§0 mục
 * 12 — không gọi Resend đồng bộ trong request).
 */
class SubscribeAction
{
    use AsAction;

    public function handle(string $fullName, string $email): NewsletterSubscriber
    {
        $subscriber      = NewsletterSubscriber::withTrashed()->firstOrNew(['email' => $email]);
        $doubleOptIn     = config('newsletter.double_opt_in');
        // Chụp lại TRƯỚC khi fill() ghi đè — cho bản ghi hoàn toàn mới, thuộc tính này chưa hề
        // được set nên là null (không phải 'active' dù cột DB có default, vì default đó chỉ áp
        // dụng ở tầng SQL, không phản ánh vào model PHP mới tạo trong bộ nhớ).
        $previousStatus  = $subscriber->status;

        if ($subscriber->trashed()) {
            $subscriber->restore(); // §0 mục 16 — khôi phục sau khi admin đã xoá thủ công
        }

        // §0 mục 14/16 — double opt-in chỉ áp dụng khi email này CHƯA TỪNG được xác nhận
        // (`confirmed_at` null) VÀ hiện KHÔNG đang active. 2 điều kiện này cùng lúc xử lý đúng
        // 3 tình huống dễ nhầm:
        //   - Subscriber đang active, lỡ submit lại form (double-click) → giữ nguyên active,
        //     KHÔNG hạ cấp xuống pending_confirmation.
        //   - Subscriber từng xác nhận (`confirmed_at` đã set), sau đó bị xoá/tự unsubscribe,
        //     giờ đăng ký lại → coi như đã chứng minh sở hữu email 1 lần rồi, KHÔNG bắt xác
        //     nhận lại — vào active ngay.
        //   - Subscriber CHƯA TỪNG xác nhận (`confirmed_at` null) mà nay bị xoá/unsubscribe rồi
        //     đăng ký lại, hoặc hoàn toàn mới → bắt xác nhận lại đúng theo cấu hình hiện tại.
        $requiresConfirmation = $doubleOptIn
            && $previousStatus !== SubscriberStatus::Active
            && is_null($subscriber->confirmed_at);

        $status = $requiresConfirmation ? SubscriberStatus::PendingConfirmation : SubscriberStatus::Active;

        $subscriber->fill([
            'full_name'       => $fullName,
            'status'          => $status,
            'source'          => $subscriber->source ?? 'public_form',
            'subscribed_at'   => $subscriber->subscribed_at ?? now(),
            'unsubscribed_at' => null,
            // §0 mục 16 — KHÔNG đụng resend_contact_id ở đây: nếu subscriber từng có (kể cả sau
            // khi bị soft-delete), giữ nguyên để SyncSubscriberToResendJob nhận ra và UPDATE
            // đúng contact cũ thay vì tạo trùng.
        ])->save();

        if ($status === SubscriberStatus::PendingConfirmation) {
            Mail::to($email)->queue(new ConfirmSubscriptionMail($subscriber));

            return $subscriber;
        }

        // Cả 2 job đều queue độc lập — lỗi/chậm bên nào không ảnh hưởng bên kia, và không
        // chặn phản hồi HTTP cho người dùng đang chờ submit form (§0 mục 12).
        SyncSubscriberToResendJob::dispatch($subscriber->id, $fullName, $email);

        // Chỉ gửi welcome mail khi đây thật sự là 1 sự kiện "bắt đầu nhận tin" mới — so với
        // $previousStatus đã chụp TRƯỚC fill()/save(), không dùng wasChanged(): với bản ghi mới
        // insert, Eloquent KHÔNG gọi syncChanges() trong performInsert() (chỉ performUpdate() có),
        // nên wasChanged() luôn trả false cho record vừa tạo — sẽ bỏ sót đúng trường hợp phổ biến
        // nhất (subscriber hoàn toàn mới) nếu dùng nó ở đây.
        if ($status === SubscriberStatus::Active && $previousStatus !== SubscriberStatus::Active) {
            Mail::to($email)->queue(new WelcomeSubscriberMail($subscriber));
        }

        return $subscriber;
    }
}

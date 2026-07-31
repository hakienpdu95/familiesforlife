<?php

namespace Modules\Newsletter\Features\PublicSubscription\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Newsletter\Enums\SubscriberStatus;
use Modules\Newsletter\Features\PublicSubscription\Actions\ConfirmSubscriptionAction;
use Modules\Newsletter\Features\PublicSubscription\Actions\SubscribeAction;
use Modules\Newsletter\Features\PublicSubscription\Data\SubscribeData;
use Modules\Newsletter\Models\NewsletterSubscriber;

class PublicSubscriptionController extends Controller
{
    public function subscribe(Request $request, SubscribeAction $action): RedirectResponse|JsonResponse
    {
        $data = SubscribeData::from($request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'email'     => ['required', 'email', 'max:255'],
        ]));

        $action->handle($data->full_name, $data->email);

        $message = 'Đã đăng ký nhận bản tin — cảm ơn bạn!';

        // Nhánh JSON cho form AJAX (vd khối newsletter trang chủ, resources/views/components/
        // frontend/newsletter-signup.blade.php) — lỗi validate (422) đã tự trả JSON sẵn qua
        // ValidationException mặc định của Laravel khi request wantsJson(), không cần xử lý
        // thêm ở đây. Nhánh redirect back() giữ nguyên cho nơi gọi bằng POST thường (submit
        // full-page, không JS) — không đổi hành vi cũ.
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return back()->with('success', $message);
    }

    /** §9.1 — chỉ có ý nghĩa khi NEWSLETTER_DOUBLE_OPT_IN=true; route ký chữ ký, không cần đăng nhập. */
    public function confirm(NewsletterSubscriber $subscriber, ConfirmSubscriptionAction $action): View
    {
        $wasPending = $subscriber->status === SubscriberStatus::PendingConfirmation;

        $action->handle($subscriber);

        return view('newsletter::public.confirmed', ['confirmed' => $wasPending]);
    }
}

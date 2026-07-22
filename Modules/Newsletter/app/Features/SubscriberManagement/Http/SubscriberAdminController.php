<?php

namespace Modules\Newsletter\Features\SubscriberManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Newsletter\Features\SubscriberManagement\Actions\RemoveSubscriberAction;
use Modules\Newsletter\Models\NewsletterSubscriber;

class SubscriberAdminController extends Controller
{
    /** Dữ liệu bảng lấy qua SubscriberApiController (Tabulator, remote pagination/sort/filter). */
    public function index(): View
    {
        $this->authorize('viewAny', NewsletterSubscriber::class);

        return view('newsletter::admin.subscribers.index');
    }

    public function destroy(Request $request, NewsletterSubscriber $subscriber, RemoveSubscriberAction $action): RedirectResponse|JsonResponse
    {
        $this->authorize('removeSubscriber', $subscriber);

        $email = $subscriber->email;

        $action->handle($subscriber);

        if ($request->expectsJson()) {
            return response()->json(['message' => "Đã xoá subscriber \"{$email}\"."]);
        }

        return back()->with('success', "Đã xoá subscriber \"{$email}\".");
    }
}

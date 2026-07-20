<?php

namespace Modules\Newsletter\Features\SubscriberManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Newsletter\Features\SubscriberManagement\Actions\RemoveSubscriberAction;
use Modules\Newsletter\Features\SubscriberManagement\Queries\ListSubscribersForAdminHandler;
use Modules\Newsletter\Features\SubscriberManagement\Queries\ListSubscribersForAdminQuery;
use Modules\Newsletter\Models\NewsletterSubscriber;

class SubscriberAdminController extends Controller
{
    public function index(Request $request, ListSubscribersForAdminHandler $handler): View
    {
        $this->authorize('viewAny', NewsletterSubscriber::class);

        $subscribers = $handler->handle(new ListSubscribersForAdminQuery(
            page: max(1, $request->integer('page', 1)),
        ));

        return view('newsletter::admin.subscribers.index', compact('subscribers'));
    }

    public function destroy(NewsletterSubscriber $subscriber, RemoveSubscriberAction $action): RedirectResponse
    {
        $this->authorize('removeSubscriber', $subscriber);

        $action->handle($subscriber);

        return back()->with('success', "Đã xoá subscriber \"{$subscriber->email}\".");
    }
}

<?php

namespace Modules\Newsletter\Features\BroadcastSending\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Newsletter\Features\BroadcastSending\Actions\SendBroadcastAction;
use Modules\Newsletter\Features\BroadcastSending\Data\BroadcastData;
use Modules\Newsletter\Models\NewsletterBroadcastLog;
use Modules\Newsletter\Models\NewsletterSubscriber;

class BroadcastAdminController extends Controller
{
    /**
     * §11 — gate bằng sendBroadcast (không phải viewAny) dù đây chỉ là trang GET: module không
     * có khái niệm "lưu nháp" (không có bảng campaign), nên content_editor xem trang soạn cũng
     * không làm được gì hữu ích (không gửi được) — tránh hiện 1 trang/nút dẫn tới ngõ cụt 403.
     */
    public function create(): View
    {
        $this->authorize('sendBroadcast', NewsletterBroadcastLog::class);

        return view('newsletter::admin.broadcast.create');
    }

    public function send(Request $request, SendBroadcastAction $action): RedirectResponse
    {
        $this->authorize('sendBroadcast', NewsletterBroadcastLog::class);

        $data = BroadcastData::from($request->validate([
            'subject'      => ['required', 'string', 'max:255'],
            'body_html'    => ['required', 'string'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ]));

        try {
            $action->handle($data->subject, $data->body_html, $data->scheduled_at);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('backend.newsletter.broadcast.logs')
            ->with('success', $data->scheduled_at ? 'Đã lên lịch gửi bản tin.' : 'Đã gửi bản tin.');
    }

    /** Dữ liệu bảng lấy qua BroadcastLogApiController (Tabulator, remote pagination/sort/filter). */
    public function logs(): View
    {
        $this->authorize('viewAny', NewsletterSubscriber::class);

        return view('newsletter::admin.broadcast.logs');
    }
}

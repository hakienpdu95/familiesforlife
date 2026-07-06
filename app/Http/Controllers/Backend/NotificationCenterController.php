<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationCenterController extends Controller
{
    public function index(Request $request): View
    {
        $user  = $request->user();
        $orgId = TenantContext::getOrganizationId();

        $query = $user->notifications()
            ->when($orgId, fn ($q, $id) =>
                $q->where(fn ($sub) =>
                    $sub->where('organization_id', $id)->orWhereNull('organization_id')
                )
            )
            ->when($request->filter === 'unread', fn ($q) => $q->whereNull('read_at'))
            ->when($request->type, fn ($q, $t) => $q->whereJsonContains('data->type', $t))
            ->latest();

        $notifications = $query->paginate(25)->withQueryString();
        $unreadCount   = $user->unreadNotifications()
            ->when($orgId, fn ($q, $id) =>
                $q->where(fn ($sub) =>
                    $sub->where('organization_id', $id)->orWhereNull('organization_id')
                )
            )
            ->count();

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    public function markRead(Request $request, string $uuid): RedirectResponse
    {
        $request->user()->notifications()->where('uuid', $uuid)->firstOrFail()->markAsRead();

        return back()->with('success', 'Đã đánh dấu đã đọc.');
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $orgId = TenantContext::getOrganizationId();

        $request->user()->unreadNotifications()
            ->when($orgId, fn ($q, $id) =>
                $q->where(fn ($sub) =>
                    $sub->where('organization_id', $id)->orWhereNull('organization_id')
                )
            )
            ->update(['read_at' => now()]);

        return back()->with('success', 'Đã đánh dấu tất cả là đã đọc.');
    }

    public function destroy(Request $request, string $uuid): RedirectResponse
    {
        $request->user()->notifications()->where('uuid', $uuid)->firstOrFail()->delete();

        return back()->with('success', 'Đã xoá thông báo.');
    }
}

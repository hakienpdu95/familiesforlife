<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\NotificationPreferenceService;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    public function __construct(
        private readonly NotificationPreferenceService $service,
    ) {}

    public function index(Request $request): View
    {
        $user  = $request->user();
        $orgId = TenantContext::getOrganizationId() ?? $user->organization_id;

        $saved = $this->service->getForUser($user, $orgId)->map(fn ($p) => [
            'channel_db'   => $p->channel_db,
            'channel_mail' => $p->channel_mail,
            'channel_push' => $p->channel_push,
        ])->toArray();

        $groups        = config('notification_types', []);
        $pushAvailable = (bool) config('webpush.vapid.public_key');

        return view('notifications.preferences', compact('groups', 'saved', 'orgId', 'pushAvailable'));
    }
}

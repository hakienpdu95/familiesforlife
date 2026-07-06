<?php

namespace App\Services;

use App\Models\NotificationPreference;
use App\Models\User;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Support\Collection;

class NotificationPreferenceService
{
    private array $prefCache = [];

    /**
     * Returns the list of channels that should be used for the given
     * (notifiable, eventType) pair, respecting stored preferences.
     *
     * Defaults (no DB record): database=on, mail=off, push=off.
     */
    /**
     * @param  bool $hasToMail  Pass method_exists($this, 'toMail') from the notification class.
     *                          When true and no preference row exists, mail is included by default.
     */
    public function channelsFor(object $notifiable, string $eventType, bool $hasToMail = false): array
    {
        if (!$notifiable instanceof User) {
            return ['database'];
        }

        $orgId    = TenantContext::getOrganizationId() ?? $notifiable->organization_id;
        $cacheKey = "{$notifiable->id}:{$orgId}:{$eventType}";

        if (!array_key_exists($cacheKey, $this->prefCache)) {
            $this->prefCache[$cacheKey] = NotificationPreference::where('user_id', $notifiable->id)
                ->where('organization_id', $orgId)
                ->where('event_type', $eventType)
                ->first();
        }

        $pref = $this->prefCache[$cacheKey];

        $dbEnabled   = $pref?->channel_db   ?? true;
        $mailEnabled = $pref?->channel_mail  ?? true;  // default on; only sent when class has toMail()
        $pushEnabled = $pref?->channel_push  ?? false;

        $channels = [];

        if ($dbEnabled) {
            $channels[] = 'database';
            $channels[] = 'broadcast';
        }

        if ($hasToMail && $mailEnabled) {
            $channels[] = 'mail';
        }

        if ($pushEnabled
            && config('webpush.vapid.public_key')
            && $notifiable->pushSubscriptions()->exists()
        ) {
            $channels[] = 'webpush';
        }

        return $channels ?: ['database'];
    }

    /** Returns saved preferences keyed by event_type. */
    public function getForUser(User $user, int $organizationId): Collection
    {
        return NotificationPreference::where('user_id', $user->id)
            ->where('organization_id', $organizationId)
            ->get()
            ->keyBy('event_type');
    }

    /** Create or update a single preference row. */
    public function upsert(
        User $user,
        int $organizationId,
        string $eventType,
        bool $channelDb,
        bool $channelMail,
        bool $channelPush,
    ): void {
        NotificationPreference::updateOrCreate(
            [
                'user_id'         => $user->id,
                'organization_id' => $organizationId,
                'event_type'      => $eventType,
            ],
            [
                'channel_db'   => $channelDb,
                'channel_mail' => $channelMail,
                'channel_push' => $channelPush,
            ],
        );
    }
}

<?php

namespace App\Shared\Notifications;

class NotificationData
{
    /**
     * Build a standardized notification payload for the `database` channel.
     *
     * All notification classes must call this method inside `toDatabase()` to
     * ensure a consistent JSON structure across every module. The frontend bell
     * dropdown and notification center both rely on these exact keys.
     *
     * @param  string  $type      Unique snake_case event key, e.g. 'sop_submitted'.
     *                            Must match an entry in the type registry (docs/notification-spec.md §4.4).
     * @param  string  $title     Short label shown in the bell dropdown (≤ 80 chars).
     * @param  string  $body      Full description shown in the notification center.
     * @param  string  $url       Route URL to navigate to on click. Use route() — never hardcode domain.
     *                            Pass an empty string when no destination exists.
     * @param  string  $icon      Icon key for the frontend icon/colour map.
     *                            Allowed: bell | check | warning | error | info | user | task | sop | kc | lead
     * @param  string  $severity  Visual severity for colour coding.
     *                            Allowed: info | success | warning | error
     * @param  array   $meta      Arbitrary context (IDs, slugs) for filtering/debugging.
     *                            Not rendered in the UI.
     *
     * @return array{type: string, title: string, body: string, url: string, icon: string, severity: string, meta: array}
     */
    public static function make(
        string $type,
        string $title,
        string $body,
        string $url      = '',
        string $icon     = 'bell',
        string $severity = 'info',
        array  $meta     = [],
    ): array {
        return compact('type', 'title', 'body', 'url', 'icon', 'severity', 'meta');
    }
}

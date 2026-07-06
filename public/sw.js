/**
 * Service Worker — Web Push handler
 * Scope: / (root of the app)
 */

const ICON   = '/favicon.ico';
const BADGE  = '/favicon.ico';

/* ── Push event ──────────────────────────────────────────────────────── */
self.addEventListener('push', event => {
    let data = {};
    try {
        data = event.data ? event.data.json() : {};
    } catch {
        data = { title: 'Thông báo mới', body: event.data?.text() ?? '' };
    }

    const title   = data.title    ?? 'Thông báo';
    const options = {
        body:    data.body    ?? '',
        icon:    ICON,
        badge:   BADGE,
        tag:     data.type    ?? 'default',     // nhóm notifications cùng type
        renotify: true,
        data:    { url: data.url ?? '/' },
        actions: data.url ? [{ action: 'open', title: 'Xem ngay' }] : [],
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

/* ── Notification click ──────────────────────────────────────────────── */
self.addEventListener('notificationclick', event => {
    event.notification.close();

    const url = event.notification.data?.url ?? '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then(windowClients => {
                // Focus existing tab if already open
                for (const client of windowClients) {
                    if (client.url === url && 'focus' in client) {
                        return client.focus();
                    }
                }
                // Otherwise open new tab
                if (clients.openWindow) {
                    return clients.openWindow(url);
                }
            })
    );
});

/* ── Install / Activate — skip waiting for faster SW updates ─────────── */
self.addEventListener('install',  () => self.skipWaiting());
self.addEventListener('activate', event => {
    event.waitUntil(clients.claim());
});

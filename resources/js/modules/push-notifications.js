/**
 * resources/js/modules/push-notifications.js
 * ─────────────────────────────────────────────
 * Browser Push via Web Push API + Service Worker.
 *
 * Exported:
 *   initPushNotifications()  — gọi 1 lần trong DOMContentLoaded
 *   Alpine.data('pushToggle') — component dùng trong notification center
 */

const SW_PATH    = '/sw.js';
const STORAGE_KEY = 'ap_push_enabled';
const SUB_API    = '/api/notifications/push-subscribe';
const UNSUB_API  = '/api/notifications/push-unsubscribe';

function getCsrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function getVapidKey() {
    return document.querySelector('meta[name="vapid-public-key"]')?.content ?? '';
}

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64  = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw     = atob(base64);
    return Uint8Array.from([...raw].map(c => c.charCodeAt(0)));
}

function isSupported() {
    return 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
}

async function getRegistration() {
    return navigator.serviceWorker.register(SW_PATH, { scope: '/' });
}

async function getCurrentSubscription() {
    const reg = await getRegistration();
    return reg.pushManager.getSubscription();
}

/* ── Subscribe ──────────────────────────────────────────────────────── */
export async function subscribePush() {
    if (!isSupported()) throw new Error('Browser không hỗ trợ Web Push.');

    const permission = await Notification.requestPermission();
    if (permission !== 'granted') throw new Error('Quyền thông báo bị từ chối.');

    const vapidKey = getVapidKey();
    if (!vapidKey) throw new Error('VAPID key chưa được cấu hình.');

    const reg = await getRegistration();
    const sub = await reg.pushManager.subscribe({
        userVisibleOnly:      true,
        applicationServerKey: urlBase64ToUint8Array(vapidKey),
    });

    const res = await fetch(SUB_API, {
        method:  'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrf(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify(sub.toJSON()),
    });

    if (!res.ok) throw new Error('Không thể lưu subscription.');

    localStorage.setItem(STORAGE_KEY, '1');
    return sub;
}

/* ── Unsubscribe ────────────────────────────────────────────────────── */
export async function unsubscribePush() {
    const sub = await getCurrentSubscription();
    if (!sub) return;

    await fetch(UNSUB_API, {
        method:  'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrf(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify({ endpoint: sub.endpoint }),
    });

    await sub.unsubscribe();
    localStorage.removeItem(STORAGE_KEY);
}

/* ── Check if currently subscribed ─────────────────────────────────── */
export async function isPushSubscribed() {
    if (!isSupported()) return false;
    const sub = await getCurrentSubscription();
    return sub !== null;
}

/* ── Auto-register SW on every page load ───────────────────────────── */
export async function initPushNotifications() {
    if (!isSupported()) return;
    try {
        await getRegistration();
    } catch (e) {
        console.warn('[push] SW registration failed:', e);
    }
}

/* ── Alpine component for the toggle button ────────────────────────── */
export function pushToggleComponent() {
    return {
        supported:   isSupported(),
        subscribed:  false,
        permission:  typeof Notification !== 'undefined' ? Notification.permission : 'default',
        loading:     false,
        error:       '',

        async init() {
            if (!this.supported) return;
            this.subscribed = await isPushSubscribed();
        },

        get label() {
            if (!this.supported) return 'Trình duyệt không hỗ trợ';
            if (this.permission === 'denied') return 'Quyền bị chặn — mở lại trong cài đặt trình duyệt';
            return this.subscribed ? 'Tắt thông báo trình duyệt' : 'Bật thông báo trình duyệt';
        },

        get icon() {
            return this.subscribed ? 'bell-slash' : 'bell';
        },

        async toggle() {
            this.loading = true;
            this.error   = '';
            try {
                if (this.subscribed) {
                    await unsubscribePush();
                    this.subscribed = false;
                } else {
                    await subscribePush();
                    this.subscribed  = true;
                    this.permission  = Notification.permission;
                }
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },
    };
}

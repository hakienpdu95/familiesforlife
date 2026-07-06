/**
 * resources/js/app.js
 * ────────────────────────────────────────────────────────────────────
 * CORE entry point — tải trên MỌI trang backend.
 *
 * Thứ tự khởi tạo quan trọng:
 *  1. jQuery     → window.$ / window.jQuery (cần trước Alpine & mọi script)
 *  2. Alpine.js  → window.Alpine (cần start() TRƯỚC DOMContentLoaded)
 *  3. iconify    → web component tự register
 *  4. admin-shell→ sidebar, dropdown, theme, shortcuts
 * ────────────────────────────────────────────────────────────────────
 */

/* ── 1. jQuery → global ─────────────────────────────────────────────
 * Expose $ và jQuery lên window để:
 *  · Script inline trong blade dùng được ngay
 */
import jQuery from 'jquery';
window.$ = window.jQuery = jQuery;

/* ── 1b. Laravel Echo + Reverb ──────────────────────────────────────
 * Pusher must be on window BEFORE Echo is instantiated.
 * Echo is only created when user-id meta tag exists (authenticated pages).
 */
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
window.Pusher = Pusher;

/* ── 2. Alpine.js ───────────────────────────────────────────────────
 *
 * Pattern chuẩn Alpine v3:
 *  - Expose window.Alpine TRƯỚC khi start() để blade có thể gọi
 *    Alpine.data(), Alpine.store(), Alpine.directive() ở script inline
 *    mà chạy TRƯỚC DOMContentLoaded
 *  - alpine:init event để đăng ký data/store/directive an toàn
 *  - start() gọi trong DOMContentLoaded (xem giải thích bên dưới)
 *
 * Không dùng @alpinejs/collapse, focus, persist... vì không có trong
 * package.json — thêm sau nếu cần bằng npm install @alpinejs/...
 */
import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * Hook: đăng ký Alpine components/stores ở đây hoặc ở blade script
 * chạy trước window.Alpine.start().
 *
 * Ví dụ trong blade:
 *   <script>
 *     document.addEventListener('alpine:init', () => {
 *       Alpine.data('dropdown', () => ({ open: false }))
 *     })
 *   </script>
 */
document.addEventListener('alpine:init', () => {
    /* ── Global Alpine stores ── */
    Alpine.store('sidebar', {
        collapsed: localStorage.getItem('ap_sidebar_collapsed') === '1',
        toggle() {
            this.collapsed = !this.collapsed;
            localStorage.setItem('ap_sidebar_collapsed', this.collapsed ? '1' : '0');
        },
    });

    Alpine.store('theme', {
        dark: localStorage.getItem('ap_theme') === 'dark',
        toggle() {
            this.dark = !this.dark;
            document.documentElement.setAttribute('data-theme', this.dark ? 'dark' : 'light');
            localStorage.setItem('ap_theme', this.dark ? 'dark' : 'light');
        },
        init() {
            document.documentElement.setAttribute('data-theme', this.dark ? 'dark' : 'light');
        },
    });

    /* ── Global Alpine data components ── */
    Alpine.data('dropdown', (options = {}) => ({
        open: false,
        toggle() { this.open = !this.open; },
        close() { this.open = false; },
        /* đóng khi click outside — dùng @click.away="close()" trong blade */
    }));

    Alpine.data('notifBell', () => ({
        open:    false,
        loading: false,
        items:   [],
        unread:  0,
        _timer:  null,
        _onVisibility: null,

        async init() {
            await this.fetchCount();
            this._startPolling();
            this._listenReverb();
        },

        async toggle() {
            this.open = !this.open;
            if (this.open && this.items.length === 0) await this.fetchItems();
        },

        async fetchCount() {
            try {
                const r = await fetch('/api/notifications/unread-count', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (r.ok) this.unread = (await r.json()).count;
            } catch {}
        },

        async fetchItems() {
            this.loading = true;
            try {
                const r = await fetch('/api/notifications?per_page=8', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (r.ok) {
                    const res = await r.json();
                    this.items  = res.data;
                    this.unread = res.meta.unread;
                }
            } catch {} finally {
                this.loading = false;
            }
        },

        async markRead(n) {
            if (n.read) return;
            n.read  = true;
            this.unread = Math.max(0, this.unread - 1);
            fetch(`/api/notifications/${n.uuid}/read`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            }).catch(() => {});
        },

        async readAll() {
            this.items.forEach(n => { n.read = true; });
            this.unread = 0;
            fetch('/api/notifications/read-all', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            }).catch(() => {});
        },

        iconBgClass(icon) {
            return 'notif-icon--' + (icon || 'bell');
        },

        iconPath(icon) {
            const p = {
                bell:    '<path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>',
                check:   '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                success: '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                warning: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
                error:   '<path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                info:    '<path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                user:    '<path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
                task:    '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>',
                sop:     '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
                kc:      '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
                lead:    '<path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
            };
            return p[icon] ?? p.bell;
        },

        _startPolling() {
            // Polling mỗi 30s; bỏ qua khi tab ẩn
            this._timer = setInterval(() => {
                if (!document.hidden) this.fetchCount();
            }, 30_000);
            // Fetch ngay khi user quay lại tab
            this._onVisibility = () => { if (!document.hidden) this.fetchCount(); };
            document.addEventListener('visibilitychange', this._onVisibility);
        },

        _listenReverb() {
            const userIdMeta = document.querySelector('meta[name="user-id"]');
            if (!window.Echo || !userIdMeta) return;
            window.Echo.private(`App.Models.User.${userIdMeta.content}`)
                .notification((n) => {
                    this.unread++;
                    if (this.open && this.items.length > 0) {
                        this.items.unshift({
                            uuid:     n.id,
                            read:     false,
                            title:    n.title    ?? '',
                            url:      n.url      ?? '',
                            icon:     n.icon     ?? 'bell',
                            time_ago: 'Vừa xong',
                        });
                    }
                });
        },

        destroy() {
            clearInterval(this._timer);
            document.removeEventListener('visibilitychange', this._onVisibility);
            if (window.Echo) {
                const meta = document.querySelector('meta[name="user-id"]');
                if (meta) window.Echo.leave(`App.Models.User.${meta.content}`);
            }
        },
    }));

    Alpine.data('pushToggle', pushToggleComponent);

    Alpine.data('confirmDelete', (options = {}) => ({
        open: false,
        targetUrl: '',
        message: options.message ?? 'Bạn có chắc muốn xoá mục này?',
        show(url) { this.targetUrl = url; this.open = true; },
        hide() { this.open = false; this.targetUrl = ''; },
        confirm() {
            if (this.targetUrl) {
                /* Gửi DELETE form — hoặc fetch tuỳ logic */
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = this.targetUrl;
                form.innerHTML = `
                    <input type="hidden" name="_token"  value="${document.querySelector('meta[name=csrf-token]')?.content}">
                    <input type="hidden" name="_method" value="DELETE">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        },
    }));
});

/*
 * Alpine.start() phải gọi SAU khi tất cả module scripts đã đăng ký
 * alpine:init listeners của chúng.
 *
 * Vì sao dùng DOMContentLoaded thay vì gọi trực tiếp:
 *  · Mọi <script type="module"> (Vite) là deferred — chạy SAU khi HTML parse xong
 *    nhưng TRƯỚC DOMContentLoaded, theo thứ tự xuất hiện trong document.
 *  · Nếu Alpine.start() chạy ngay trong app.js, nó xử lý DOM trước khi
 *    các module script sau (lead.js, organization.js...) kịp đăng ký
 *    alpine:init listener → "orgListPage is not defined".
 *  · Đặt vào DOMContentLoaded đảm bảo TẤT CẢ module scripts đã chạy
 *    xong và đăng ký xong trước khi Alpine.start() fire alpine:init.
 */
document.addEventListener('DOMContentLoaded', () => Alpine.start(), { once: true });

/* ── 3. Iconify web component ───────────────────────────────────────
 * Self-registers as <iconify-icon> custom element.
 * Tải icon on-demand qua CDN API — không cần bundle icon files.
 *
 * Dùng trong blade:
 *   <iconify-icon icon="mdi:home"></iconify-icon>
 *   <iconify-icon icon="heroicons:user" width="20"></iconify-icon>
 */
import 'iconify-icon';

/* ── 4. Admin shell ─────────────────────────────────────────────────
 * Sidebar collapse/mobile, dropdown fallback (JS thuần — Alpine
 * là opt-in, không bắt buộc cho mọi element), theme, shortcuts.
 */
import { initAdminShell } from './admin-shell.js';
import { initPushNotifications, pushToggleComponent } from './modules/push-notifications.js';

/* ── 5. Form validation helper ──────────────────────────────────────
 * Lightweight (~2 KB), không phụ thuộc thư viện. Exposed lên window
 * để mọi module gọi initFormValidation() mà không cần load thêm bundle.
 */
import './modules/form-validation.js';

/* ── 6. AI Copilot task widget ──────────────────────────────────────
 * Exposed on window so blade templates can use:
 *   x-data="aiTask({agentSlug:'xxx', variables:{...}})"
 *   x-data="{ ...aiTask({...}), extraMethod() { ... } }"
 */
import { aiTask } from './components/aiTask.js';
window.aiTask = aiTask;

document.addEventListener('DOMContentLoaded', () => {
    initAdminShell();
    initPushNotifications(); // register SW on every page

    // Initialize Echo only for authenticated pages
    const userIdMeta = document.querySelector('meta[name="user-id"]');
    if (userIdMeta && import.meta.env.VITE_REVERB_APP_KEY) {
        const _tls = (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https';
        window.Echo = new Echo({
            broadcaster:       'reverb',
            key:               import.meta.env.VITE_REVERB_APP_KEY,
            wsHost:            import.meta.env.VITE_REVERB_HOST ?? 'localhost',
            wsPort:            import.meta.env.VITE_REVERB_PORT ?? 80,
            wssPort:           import.meta.env.VITE_REVERB_PORT ?? 443,
            forceTLS:          _tls,
            enabledTransports: _tls ? ['wss'] : ['ws'],
            disableStats:      true,
            auth: {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
            },
        });
    }
}, { once: true });

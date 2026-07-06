/**
 * resources/js/admin-shell.js
 * ─────────────────────────────────────────────────────────────────────
 * Toàn bộ logic giao diện admin shell:
 *  · Sidebar collapse/expand (desktop)
 *  · Sidebar drawer (mobile)
 *  · Dropdown menus (notifications, user)
 *  · Theme toggle (light / dark)
 *  · Keyboard shortcut ⌘K / Ctrl+K → focus search
 *  · Sidebar accordion (đóng menu khác khi mở)
 * ─────────────────────────────────────────────────────────────────────
 */

const SK = 'ap_sidebar_collapsed'; // localStorage key — sidebar state
const TK = 'ap_theme';             // localStorage key — theme

/* ────────────────────────────────────────────────────────────────────
   SIDEBAR
   ──────────────────────────────────────────────────────────────────── */

function initSidebar() {
    const sidebar    = document.getElementById('sidebar');
    const mainArea   = document.getElementById('mainArea');
    const overlay    = document.getElementById('sidebarOverlay');
    const mobileBtn  = document.getElementById('mobileSidebarBtn');
    const desktopBtn = document.getElementById('desktopCollapseBtn');

    if (!sidebar) return;

    /* Cập nhật button nào hiển thị theo breakpoint */
    function syncButtons() {
        const isMobile = window.innerWidth < 1024;
        if (mobileBtn)  mobileBtn.style.display  = isMobile ? 'flex' : 'none';
        if (desktopBtn) desktopBtn.style.display = isMobile ? 'none' : 'flex';
        if (!isMobile) closeMobileSidebar();
    }

    /* Desktop: collapse / expand */
    function applyCollapsed(collapsed) {
        sidebar.classList.toggle('collapsed', collapsed);
        mainArea?.classList.toggle('sidebar-collapsed', collapsed);
        localStorage.setItem(SK, collapsed ? '1' : '0');
    }

    /* Mobile: open */
    function openMobileSidebar() {
        sidebar.classList.add('mobile-open');
        overlay?.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    /* Mobile: close */
    function closeMobileSidebar() {
        sidebar.classList.remove('mobile-open');
        overlay?.classList.remove('active');
        document.body.style.overflow = '';
    }

    /* Khôi phục trạng thái lưu */
    applyCollapsed(localStorage.getItem(SK) === '1');

    /* Events */
    desktopBtn?.addEventListener('click', () =>
        applyCollapsed(!sidebar.classList.contains('collapsed'))
    );

    mobileBtn?.addEventListener('click', () => {
        sidebar.classList.contains('mobile-open')
            ? closeMobileSidebar()
            : openMobileSidebar();
    });

    overlay?.addEventListener('click', closeMobileSidebar);

    window.addEventListener('resize', syncButtons);
    syncButtons();

    /* Accordion: đóng details khác khi mở một details */
    document.querySelectorAll('nav details').forEach(det => {
        det.addEventListener('toggle', () => {
            if (det.open) {
                document.querySelectorAll('nav details').forEach(other => {
                    if (other !== det && other.open) other.removeAttribute('open');
                });
            }
        });
    });
}

/* ────────────────────────────────────────────────────────────────────
   DROPDOWNS
   ──────────────────────────────────────────────────────────────────── */

function initDropdowns() {
    function toggleDD(panelId) {
        const panel  = document.getElementById(panelId);
        const wasOpen = panel?.classList.contains('open');
        // Đóng tất cả
        document.querySelectorAll('.dd-panel').forEach(p => p.classList.remove('open'));
        // Mở cái được click (nếu chưa mở)
        if (!wasOpen) panel?.classList.add('open');
    }

    // notifPanel is managed by Alpine notifBell() component — no handler here
    document.getElementById('userBtn')
        ?.addEventListener('click',  e => { e.stopPropagation(); toggleDD('userPanel'); });

    // Đóng khi click ra ngoài
    document.addEventListener('click', () =>
        document.querySelectorAll('.dd-panel').forEach(p => p.classList.remove('open'))
    );

    // Đóng khi nhấn Escape
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.dd-panel').forEach(p => p.classList.remove('open'));
        }
    });
}

/* ────────────────────────────────────────────────────────────────────
   THEME
   ──────────────────────────────────────────────────────────────────── */

function initTheme() {
    const themeBtn = document.getElementById('themeBtn');
    const iconSun  = document.getElementById('iconSun');
    const iconMoon = document.getElementById('iconMoon');
    const sidebar  = document.getElementById('sidebar');
    const topbar   = document.querySelector('.topbar');
    const footer   = document.querySelector('.page-footer');

    function applyTheme(dark) {
        document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');

        const surf = dark ? '#1e293b' : '#ffffff';
        const bord = dark ? '#334155' : '#e2e8f0';
        const bg   = dark ? '#0f172a' : '#f1f5f9';

        document.body.style.background = bg;

        if (sidebar) {
            sidebar.style.background      = surf;
            sidebar.style.borderRightColor = bord;
        }
        if (topbar) {
            topbar.style.background          = surf;
            topbar.style.borderBottomColor   = bord;
        }
        if (footer) {
            footer.style.background        = surf;
            footer.style.borderTopColor    = bord;
        }

        if (iconSun)  iconSun.style.display  = dark ? 'none'  : 'block';
        if (iconMoon) iconMoon.style.display = dark ? 'block' : 'none';

        localStorage.setItem(TK, dark ? 'dark' : 'light');
    }

    // Khôi phục theme đã lưu
    applyTheme(localStorage.getItem(TK) === 'dark');

    themeBtn?.addEventListener('click', () =>
        applyTheme(localStorage.getItem(TK) !== 'dark')
    );
}

/* ────────────────────────────────────────────────────────────────────
   KEYBOARD SHORTCUTS
   ──────────────────────────────────────────────────────────────────── */

function initShortcuts() {
    document.addEventListener('keydown', e => {
        // ⌘K / Ctrl+K → focus search
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            document.querySelector('.search-box input')?.focus();
        }
    });
}

/* ────────────────────────────────────────────────────────────────────
   EXPORT
   ──────────────────────────────────────────────────────────────────── */

export function initAdminShell() {
    initSidebar();
    initDropdowns();
    initTheme();
    initShortcuts();
}

/**
 * resources/js/admin-shell.js
 * ─────────────────────────────────────────────────────────────────────
 * Toàn bộ logic giao diện admin shell:
 *  · Sidebar collapse/expand (desktop)
 *  · Sidebar drawer (mobile)
 *  · Dropdown menus (notifications, user)
 *  · Keyboard shortcut ⌘K / Ctrl+K → focus search
 *  · Sidebar accordion (đóng menu khác khi mở)
 * ─────────────────────────────────────────────────────────────────────
 */

const SK = 'ap_sidebar_collapsed'; // localStorage key — sidebar state

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
    initShortcuts();
}

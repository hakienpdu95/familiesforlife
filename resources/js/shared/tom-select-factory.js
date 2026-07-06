/**
 * resources/js/shared/tom-select-factory.js
 * ─────────────────────────────────────────────────────────────────────
 * Factory khởi tạo TomSelect với config chuẩn hệ thống.
 * Wrap window.TomSelect (đã load bởi @vite(['resources/js/modules/tom-select.js'])).
 *
 * Cách dùng trong module page controller:
 *   import { createTs, createTsRemote } from '@shared/tom-select-factory.js';
 *
 *   createTs('#ts-stage',  { placeholder: '— Chọn tình trạng —' });
 *   createTsRemote('#ts-user', {
 *       url:         '/api/users',
 *       valueField:  'id',
 *       labelField:  'text',
 *       searchField: ['text', 'email'],
 *   });
 * ─────────────────────────────────────────────────────────────────────
 */

const DEFAULTS = {
    create:         false,
    maxOptions:     200,
    dropdownParent: 'body',
    plugins:        ['clear_button'],
    render: {
        no_results: () =>
            `<div class="no-results" style="padding:.75rem;font-size:.875rem;color:#94a3b8;text-align:center">
                Không tìm thấy kết quả
             </div>`,
    },
};

/**
 * Tạo TomSelect cơ bản (static options).
 *
 * @param {string|HTMLElement} selector
 * @param {Object}             opts       - TomSelect options
 * @returns {TomSelect|null}
 */
export function createTs(selector, opts = {}) {
    if (!window.TomSelect) {
        console.warn('[TS] TomSelect chưa load. Thêm @vite tom-select.js vào trang.');
        return null;
    }
    const el = _resolve(selector);
    if (!el) return null;

    return new window.TomSelect(el, { ...DEFAULTS, ...opts });
}

/**
 * Tạo TomSelect với remote search.
 *
 * @param {string|HTMLElement} selector
 * @param {Object} opts
 * @param {string} opts.url          - endpoint (nhận ?q=)
 * @param {string} opts.valueField   - default 'id'
 * @param {string} opts.labelField   - default 'text'
 * @param {string[]} opts.searchField
 * @param {Function} opts.onChange   - callback (value) => void
 */
export function createTsRemote(selector, opts = {}) {
    const {
        url,
        valueField  = 'id',
        labelField  = 'text',
        searchField = ['text'],
        onChange,
        ...rest
    } = opts;

    if (!url) {
        console.warn('[TS] createTsRemote: thiếu opts.url');
        return null;
    }

    return createTs(selector, {
        valueField,
        labelField,
        searchField,
        load(query, callback) {
            const endpoint = url + (url.includes('?') ? '&' : '?') + 'q=' + encodeURIComponent(query);
            fetch(endpoint)
                .then(r => r.json())
                .then(callback)
                .catch(() => callback());
        },
        onChange: onChange ?? undefined,
        ...rest,
    });
}

/**
 * Tạo TomSelect cho trường chọn nhân viên phụ trách.
 * Pattern phổ biến trong Lead, Task, Workflow.
 *
 * @param {string} selector
 * @param {string} apiUrl       - URL từ data attribute (data-assignable-url)
 * @param {Function} onChange
 */
export function createTsAssignee(selector, apiUrl, onChange) {
    return createTsRemote(selector, {
        url:        apiUrl,
        valueField: 'id',
        labelField: 'text',
        searchField: ['text', 'email'],
        placeholder: '— Chưa phân công —',
        // Mặc định TomSelect chỉ load() khi có ký tự gõ vào (shouldLoad yêu cầu
        // query.length > 0) — danh sách nhân viên theo org sẽ trống cho tới khi
        // gõ. preload: true gọi load('') ngay khi khởi tạo để hiện đủ danh sách.
        preload: true,
        onChange,
        render: {
            option: (data) =>
                `<div class="flex items-center gap-2 py-0.5">
                    <span class="text-sm font-medium">${data.text}</span>
                    ${data.email ? `<span class="text-xs opacity-50">${data.email}</span>` : ''}
                 </div>`,
            item: (data) =>
                `<div class="flex items-center gap-1">
                    <span>${data.text}</span>
                 </div>`,
            no_results: () =>
                `<div class="no-results" style="padding:.75rem;font-size:.875rem;color:#94a3b8;text-align:center">
                    Không tìm thấy nhân viên
                 </div>`,
        },
    });
}

/**
 * Auto-init TomSelect cho tất cả `select.ts-init` trong container.
 *
 * Cách dùng trong module page controller:
 *   import { initAllTomSelects } from '@shared/tom-select-factory.js';
 *   initAllTomSelects(form);   // gọi 1 lần, tự tìm mọi select.ts-init
 *
 * Placeholder ưu tiên:
 *   1. data-ts-placeholder attribute trên <select>
 *   2. Text của <option value=""> đầu tiên
 *   3. '— Chọn —' (fallback mặc định)
 *
 * Select đã có .tomselect (đã init) → bỏ qua, không init lại.
 * Select cần config đặc biệt (onChange, cascade...) → không thêm ts-init,
 * gọi createTs() thủ công sau.
 *
 * @param {HTMLElement|Document} container  - form hoặc document
 * @param {Function} [afterInit]            - callback chạy sau khi tất cả TS đã init
 */
export function initAllTomSelects(container = document, afterInit) {
    if (!window.TomSelect) {
        console.warn('[TS] TomSelect chưa load. Thêm @vite tom-select.js trước module JS.');
        return;
    }
    for (const el of container.querySelectorAll('select.ts-init, select[data-ts-remote-url]')) {
        if (el.tomselect) continue;
        const placeholder = el.dataset.tsPlaceholder
            || el.querySelector('option[value=""]')?.textContent.trim()
            || '— Chọn —';
        const remoteUrl = el.dataset.tsRemoteUrl;
        if (remoteUrl) {
            createTsRemote(el, { url: remoteUrl, placeholder });
        } else {
            createTs(el, { placeholder });
        }
    }
    afterInit?.();
}

/**
 * Destroy TomSelect instance an toàn.
 * Dùng khi Alpine component unmount.
 */
export function destroyTs(instance) {
    try { instance?.destroy(); } catch (_) { /* ignore */ }
}

// ── Internal ──────────────────────────────────────────────────────────

function _resolve(selector) {
    const el = typeof selector === 'string'
        ? document.querySelector(selector)
        : selector;
    if (!el) {
        console.warn('[TS] Element không tìm thấy:', selector);
        return null;
    }
    return el;
}

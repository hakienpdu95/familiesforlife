/**
 * pages/article-form.js
 *
 * Responsibilities:
 *   1. Inline validation — delegate to global initFormValidation
 *   2. Tab-aware submit guard — phát hiện required field trống ở tab ẩn,
 *      chuyển tab, hiện Toast trước khi initFormValidation validate inline
 *   3. TomSelect — auto-init select.ts-init (định dạng nội dung)
 *   4. Tags — biến input thường thành TomSelect tag-input (create-on-type,
 *      vẫn submit dạng chuỗi phân tách dấu phẩy qua name="tags" — không đổi contract backend)
 *   5. Flatpickr — auto-init input.fp-init (vd scheduled_at) trên toàn trang, KHÔNG scope theo
 *      `form` — field lên lịch xuất bản nằm ở 1 <form> riêng trong sidebar "Trạng thái", không
 *      lồng được trong <form data-article-form> chính (HTML không cho phép <form> lồng nhau)
 *
 * Requires globals (core bundle): initFormValidation, window.Alpine, window.Toast
 * Requires globals (lazy bundle):  window.TomSelect (tom-select.js), initAllDatePickers (flatpickr.js)
 *
 * Không đụng tới post-block-composer.js — composer tự tìm form qua .closest('form')
 * và tự đồng bộ blocks_json khi submit, độc lập với guard này.
 */

import { createTs, initAllTomSelects } from '@shared/tom-select-factory.js';

// ── Constants ──────────────────────────────────────────────────────────────

const FORM_SEL = '[data-article-form]';
const RE_TAB_XSHOW = /tab\s*===\s*['"](\w+)['"]/;

// ── Entry point ────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    window.initAllDatePickers?.();

    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    _setupTabGuard(form);
    initAllTomSelects(form);
    _setupTagsInput(form);
    _setupCategoryPicker();
    _setupOcopProductPicker();
});

// ── Tab-aware submit guard ─────────────────────────────────────────────────

function _setupTabGuard(form) {
    let wrapper = null;

    form.addEventListener('submit', (e) => {
        const errors = _collectHiddenErrors(form);
        if (!errors.size) return;

        e.preventDefault();

        wrapper ??= form.closest('[x-data]') ?? document.querySelector('[x-data]');
        _switchAlpineTab(wrapper, errors.keys().next().value);
        _toastHiddenErrors(errors);
    }, /* capture */ true);
}

function _collectHiddenErrors(form) {
    const map = new Map();

    for (const field of form.querySelectorAll('[data-req]')) {
        if (field.value.trim()) continue;

        const panel = field.closest('[x-show]');
        if (!panel || panel.style.display !== 'none') continue;

        const tabKey = RE_TAB_XSHOW.exec(panel.getAttribute('x-show') ?? '')?.[1];
        if (!tabKey) continue;

        if (!map.has(tabKey)) {
            map.set(tabKey, { label: panel.dataset.tabLabel ?? tabKey, fields: [] });
        }
        map.get(tabKey).fields.push(_resolveFieldLabel(field));
    }

    return map;
}

function _resolveFieldLabel(field) {
    const labelText = field.closest('.form-control')
        ?.querySelector('.label-text')
        ?.textContent.replace(/\s*\*\s*$/, '').trim();
    return labelText || field.placeholder || field.name || 'Trường bắt buộc';
}

function _switchAlpineTab(wrapper, tabKey) {
    if (!wrapper) return;
    try {
        const data = window.Alpine?.$data(wrapper);
        if (data?.tab !== undefined) data.tab = tabKey;
    } catch { /* Alpine chưa mount — bỏ qua */ }
}

function _toastHiddenErrors(errors) {
    if (!window.Toast) return;

    const lines = Array.from(errors.values(), ({ label, fields }) =>
        `${label}: ${fields.join(', ')}`
    );

    Toast.warning(`Còn thiếu thông tin bắt buộc:\n${lines.join('\n')}`, {
        duration: 5000,
    });
}

// ── Tags — TomSelect tag-input trên input thường ────────────────────────────

/**
 * Biến <input name="tags"> (chuỗi "a, b, c") thành TomSelect tag-input:
 * gõ + Enter/phẩy để tạo tag mới, click x để xoá. TomSelect gắn trực tiếp lên
 * <input> (không cần <select>) vẫn submit đúng name="tags" dạng chuỗi phân
 * tách bởi delimiter — không cần đổi validation/Data class ở backend.
 */
function _setupTagsInput(form) {
    const input = form.querySelector('[name="tags"]');
    if (!input || input.tomselect) return;

    createTs(input, {
        create:          true,
        persist:         false,
        createOnBlur:    true,
        delimiter:       ',',
        hidePlaceholder: true,
        plugins:         ['remove_button'],
        placeholder:     'Gõ tag rồi Enter — VD: ngủ, sơ sinh, mẹo hay',
    });
}

// ── Danh mục — cây phân cấp + ★ đặt danh mục chính ──────────────────────────

/**
 * Picker danh mục render bởi post::admin.articles._category-picker (đệ quy cha/con/cháu...).
 * 2 hành vi:
 *   1. Bấm ★ (button[data-cat-star]) → ghi id vào hidden input is_primary_category_id, cập
 *      nhật màu ★ (chỉ 1 nút đang active), tự tick checkbox tương ứng nếu chưa tick (danh mục
 *      chính bắt buộc phải nằm trong category_ids[]).
 *   2. Bỏ tick checkbox[data-cat-check] của danh mục đang là "chính" → tự gỡ trạng thái chính,
 *      tránh submit is_primary_category_id trỏ tới 1 danh mục không được chọn.
 *
 * Tra bằng document, KHÔNG scope theo `form` (FORM_SEL) — trang edit có nhiều <form> độc lập
 * (nội dung bản dịch riêng, "Cài đặt chung" sidebar riêng — HTML không cho <form> lồng nhau) và
 * picker danh mục nằm trong form sidebar, không phải form nội dung [data-article-form].
 */
function _setupCategoryPicker() {
    const container = document.querySelector('[data-cat-picker]');
    const primaryInput = document.querySelector('[data-cat-primary-input]');
    if (!container || !primaryInput) return;

    container.addEventListener('click', (e) => {
        const star = e.target.closest('[data-cat-star]');
        if (!star) return;

        const id = star.dataset.catStar;
        primaryInput.value = id;
        _paintStars(container, id);

        const checkbox = container.querySelector(`[data-cat-check="${id}"]`);
        if (checkbox && !checkbox.checked) checkbox.checked = true;
    });

    container.addEventListener('change', (e) => {
        const checkbox = e.target.closest('[data-cat-check]');
        if (!checkbox || checkbox.checked) return;
        if (primaryInput.value !== checkbox.dataset.catCheck) return;

        primaryInput.value = '';
        _paintStars(container, null);
    });
}

function _paintStars(container, activeId) {
    container.querySelectorAll('[data-cat-star]').forEach((btn) => {
        const active = activeId !== null && btn.dataset.catStar === activeId;
        btn.classList.toggle('text-warning', active);
        btn.classList.toggle('text-base-content/20', !active);
        btn.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
}

// ── Sản phẩm OCOP liên quan — load động theo province_code/ward_code ───────

/**
 * spec/Province_Showcase_Technical_Specification.md §3.4.1 — chỉ có ở form sửa bài viết
 * (#ocop-product-picker không tồn tại ở create form). Lắng nghe "address-picker:change" (phát
 * bởi resources/views/components/address-picker.blade.php) khớp đúng instance-id của
 * <x-address-picker> trong form này, rồi gọi GET /api/ocop-products/picker để load lại danh
 * sách — ưu tiên ward_code (chuyên sâu hơn), fallback province_code. Sản phẩm đã tick vẫn được
 * giữ lại dù không còn khớp bộ lọc mới (tránh mất liên kết cũ ngoài ý muốn khi lưu).
 */
function _setupOcopProductPicker() {
    const container = document.getElementById('ocop-product-picker');
    if (!container) return;

    const instanceId = container.dataset.instanceId;
    const list = container.querySelector('[data-ocop-picker-list]');
    if (!list) return;

    /** @type {Map<string, {name: string, place: string}>} id (string) → nhãn hiển thị */
    const checked = new Map();
    _syncCheckedState();

    document.addEventListener('address-picker:change', (e) => {
        if (e.detail.instanceId !== instanceId) return;
        _loadProducts(e.detail.provinceCode, e.detail.wardCode);
    });

    function _syncCheckedState() {
        list.querySelectorAll('input[type="checkbox"]').forEach((cb) => {
            if (cb.checked) {
                checked.set(cb.value, { name: cb.dataset.name ?? '', place: cb.dataset.place ?? '' });
            } else {
                checked.delete(cb.value);
            }
        });
    }

    async function _loadProducts(provinceCode, wardCode) {
        _syncCheckedState();

        if (!provinceCode && !wardCode) {
            _render([]);
            return;
        }

        list.innerHTML = '<p class="text-xs text-base-content/30 py-1">Đang tải...</p>';

        const params = new URLSearchParams();
        if (wardCode) params.set('ward_code', wardCode);
        else params.set('province_code', provinceCode);

        try {
            const res = await fetch('/api/ocop-products/picker?' + params.toString());
            if (!res.ok) throw new Error('HTTP ' + res.status);
            _render(await res.json());
        } catch (err) {
            console.error('[ocopPicker] Lỗi tải sản phẩm OCOP:', err);
            list.innerHTML = '<p class="text-xs text-error py-1">Lỗi tải danh sách sản phẩm OCOP.</p>';
        }
    }

    function _render(products) {
        const seen = new Set();
        const rows = [];

        for (const p of products) {
            const id = String(p.id);
            seen.add(id);
            rows.push(_row(id, p.name, p.ward_name || p.province_name || '', checked.has(id)));
        }

        // Giữ sản phẩm đã tick nhưng không còn khớp bộ lọc mới — không âm thầm bỏ liên kết cũ.
        for (const [id, info] of checked) {
            if (seen.has(id)) continue;
            rows.push(_row(id, info.name, info.place, true));
        }

        list.innerHTML = rows.length
            ? rows.join('')
            : '<p class="text-xs text-base-content/30 py-1">Không có sản phẩm OCOP nào khớp tỉnh/phường đã chọn.</p>';
    }

    function _row(id, name, place, isChecked) {
        return `<label class="flex items-center gap-2 cursor-pointer text-xs py-0.5">
            <input type="checkbox" name="ocop_product_ids[]" value="${id}"
                   data-name="${_esc(name)}" data-place="${_esc(place)}"
                   class="checkbox checkbox-xs shrink-0" ${isChecked ? 'checked' : ''}>
            <span class="flex-1">${_esc(name)}</span>
            ${place ? `<span class="text-base-content/40">${_esc(place)}</span>` : ''}
        </label>`;
    }

    function _esc(str) {
        return String(str ?? '').replace(/[&<>"']/g, (c) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        }[c]));
    }
}

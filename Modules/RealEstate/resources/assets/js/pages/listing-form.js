/**
 * pages/listing-form.js
 *
 * Responsibilities:
 *   1. Inline validation — delegate to global initFormValidation (blur + format + submit)
 *   2. Tab-aware submit guard — phát hiện required field trống ở tab ẩn (kể cả field
 *      required động qua :data-req của Alpine, ví dụ price/monthly_rent theo is_price_negotiable),
 *      chuyển tab, hiện Toast trước khi initFormValidation validate inline
 *   3. Slug auto-fill — sinh slug từ tiêu đề tin (Vietnamese-aware), khoá lại ngay khi user tự sửa
 *   4. TomSelect:
 *      - listing_type: options tĩnh → ts-init
 *      - property_type: cascade theo listing_type (§22.5) → KHÔNG ts-init, JS quản lý options
 *      - house_subtype/apartment_subtype/legal_status/direction/balcony_direction/usage_status/
 *        interior_status: nằm trong tab "Đặc điểm" (ẩn mặc định, tab đầu là "basic") VÀ có thể
 *        ẩn thêm lần nữa theo property_type → KHÔNG ts-init (zero-width bug, §22.6), init khi
 *        panel + mọi x-show tổ tiên đều đang hiện
 *
 * Requires globals (core bundle): initFormValidation, window.Alpine, window.Toast
 * Requires globals (lazy bundle):  window.TomSelect (tom-select.js)
 */

import { createTs, initAllTomSelects } from '@shared/tom-select-factory.js';

// ── Constants & lookup tables ──────────────────────────────────────────────

/** Selector phải khớp với attribute trên form trong blade. */
const FORM_SEL = '[data-realestate-form]';

/** Regex trích tên tab từ x-show="tab === 'basic'" — compile 1 lần, dùng mãi. */
const RE_TAB_XSHOW = /tab\s*===\s*['"](\w+)['"]/;

/** Select cần init trễ (nằm trong tab không active mặc định + có thể ẩn thêm theo property_type). */
const LAZY_SELECT_IDS = [
    'ts-house_subtype', 'ts-apartment_subtype', 'ts-legal_status',
    'ts-direction', 'ts-balcony_direction', 'ts-usage_status', 'ts-interior_status',
];

/**
 * Bảng chuyển ký tự tiếng Việt → Latin cho slug.
 * Object.freeze() để V8 tối ưu làm hidden class cố định, tránh re-shape.
 */
const VI_MAP = Object.freeze({
    à:'a', á:'a', ả:'a', ã:'a', ạ:'a',
    ă:'a', ằ:'a', ắ:'a', ẳ:'a', ẵ:'a', ặ:'a',
    â:'a', ầ:'a', ấ:'a', ẩ:'a', ẫ:'a', ậ:'a',
    è:'e', é:'e', ẻ:'e', ẽ:'e', ẹ:'e',
    ê:'e', ề:'e', ế:'e', ể:'e', ễ:'e', ệ:'e',
    ì:'i', í:'i', ỉ:'i', ĩ:'i', ị:'i',
    ò:'o', ó:'o', ỏ:'o', õ:'o', ọ:'o',
    ô:'o', ồ:'o', ố:'o', ổ:'o', ỗ:'o', ộ:'o',
    ơ:'o', ờ:'o', ớ:'o', ở:'o', ỡ:'o', ợ:'o',
    ù:'u', ú:'u', ủ:'u', ũ:'u', ụ:'u',
    ư:'u', ừ:'u', ứ:'u', ử:'u', ữ:'u', ự:'u',
    ỳ:'y', ý:'y', ỷ:'y', ỹ:'y', ỵ:'y',
    đ:'d',
});

// ── Entry point ────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    _setupTabGuard(form);
    _setupSlugAutoFill(form);
    initAllTomSelects(form);
    _setupPropertyTypeCascade(form);
    _setupLazySelectReveal(form);
});

// ── Tab-aware submit guard ─────────────────────────────────────────────────

/**
 * Chạy ở capture phase (trước initFormValidation's bubble handler).
 * Nếu có required field trống trên tab ẩn (kể cả field có :data-req động — ví dụ
 * price/monthly_rent chỉ required khi !isNegotiable):
 *   - Chuyển sang tab đó qua Alpine.$data()
 *   - Hiện Toast liệt kê tab + field
 *   - preventDefault để dừng submit
 * initFormValidation vẫn chạy sau (bubble) và sẽ highlight inline error trên tab vừa switch về.
 */
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

/**
 * Quét [data-req] toàn form, thu thập những field trống đang nằm ở tab ẩn.
 * @returns {Map<string, {label:string, fields:string[]}>}
 */
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

/** Lấy tên hiển thị của field: label-text → placeholder → name attribute. */
function _resolveFieldLabel(field) {
    const labelText = field.closest('.form-control')
        ?.querySelector('.label-text')
        ?.textContent.replace(/\s*\*\s*$/, '').trim();
    return labelText || field.placeholder || field.name || 'Trường bắt buộc';
}

/** Chuyển tab qua Alpine reactive data — đồng bộ, không cần event. */
function _switchAlpineTab(wrapper, tabKey) {
    if (!wrapper) return;
    try {
        const data = window.Alpine?.$data(wrapper);
        if (data?.tab !== undefined) data.tab = tabKey;
    } catch { /* Alpine chưa mount — bỏ qua */ }
}

/** Hiện Toast.warning() liệt kê từng tab + field còn thiếu. */
function _toastHiddenErrors(errors) {
    if (!window.Toast) return;

    const lines = Array.from(errors.values(), ({ label, fields }) =>
        `${label}: ${fields.join(', ')}`
    );

    Toast.warning(`Còn thiếu thông tin bắt buộc:\n${lines.join('\n')}`, {
        duration: 5000,
    });
}

// ── Slug auto-fill ─────────────────────────────────────────────────────────

/**
 * Tự động điền slug khi user gõ tiêu đề tin.
 * Dừng auto-fill ngay khi user tự chỉnh slug (locked = true).
 * Trên trang edit slug đã có giá trị → locked từ đầu.
 */
function _setupSlugAutoFill(form) {
    const titleInput = form.querySelector('[name="title"]');
    const slugInput  = form.querySelector('[name="slug"]');
    if (!titleInput || !slugInput) return;

    let locked = slugInput.value.trim() !== '';

    slugInput.addEventListener('input', () => {
        locked = slugInput.value.trim() !== '';
    }, { passive: true });

    slugInput.addEventListener('change', () => {
        if (!slugInput.value.trim()) locked = false;
    }, { passive: true });

    titleInput.addEventListener('input', () => {
        if (locked) return;
        slugInput.value = _toSlug(titleInput.value);
    }, { passive: true });
}

/**
 * Chuyển chuỗi tiếng Việt sang slug URL-safe.
 * Pipeline: lowercase → map VI chars → strip non-alphanumeric → trim → spaces→hyphens → dedupe hyphens
 */
function _toSlug(str) {
    let out = '';
    for (const ch of str.toLowerCase()) {
        out += VI_MAP[ch] ?? ch;
    }
    return out
        .replace(/[^a-z0-9\s-]/g, '')
        .trim()
        .replace(/\s+/g, '-')
        .replace(/-{2,}/g, '-');
}

// ── property_type cascade (§22.5) ───────────────────────────────────────────

/**
 * property_type phụ thuộc listing_type (Đất thổ cư chỉ hợp lệ khi Bán, Mặt bằng chỉ hợp lệ khi
 * Thuê) — KHÔNG dùng ts-init, tự quản lý option list qua clearOptions()/addOption() mỗi khi
 * listing_type đổi (đúng pattern cascade province→ward, §22.5).
 */
function _setupPropertyTypeCascade(form) {
    const ptEl = form.querySelector('#ts-property_type');
    const ltEl = form.querySelector('[name="listing_type"]');
    if (!ptEl || !ltEl || !window.TomSelect) return;

    const allOptions = Array.from(ptEl.querySelectorAll('option[value]:not([value=""])')).map(o => ({
        value: o.value,
        text:  o.textContent.trim(),
        types: (o.dataset.listingType || '').split(' '),
    }));

    const ts = createTs(ptEl, { placeholder: ptEl.dataset.tsPlaceholder || '— Chọn loại hình —' });
    if (!ts) return;

    const applyFilter = () => {
        const lt = ltEl.value;
        const current = ts.getValue();

        ts.clearOptions();
        allOptions.filter(o => o.types.includes(lt)).forEach(o => ts.addOption({ value: o.value, text: o.text }));
        ts.refreshOptions(false);

        const stillValid = allOptions.some(o => o.value === current && o.types.includes(lt));
        ts.setValue(stillValid ? current : '');
    };

    applyFilter();
    ltEl.addEventListener('change', applyFilter);
}

// ── Lazy TomSelect cho select trong tab/section ẩn (§22.6) ──────────────────

/**
 * house_subtype/apartment_subtype/legal_status/... nằm trong tab "Đặc điểm" (không active mặc
 * định) và có thể ẩn thêm lần nữa theo property_type — init ts-init ngay từ đầu sẽ đo kích thước
 * = 0 (panel display:none) → dropdown vỡ layout. Init thủ công mỗi khi:
 *   - user click sang tab bất kỳ (có thể là tab "Đặc điểm")
 *   - listing_type/property_type đổi (điều kiện x-show lồng bên trong panel thay đổi)
 *   - ngay khi trang load (trường hợp old() redirect back đã ở sẵn tab "Đặc điểm")
 */
function _setupLazySelectReveal(form) {
    if (!window.TomSelect) return;

    const tryInitAll = () => {
        for (const id of LAZY_SELECT_IDS) {
            const el = form.querySelector(`#${id}`);
            if (!el || el.tomselect) continue;
            if (_isHiddenByAncestor(el)) continue;
            createTs(el, { placeholder: el.dataset.tsPlaceholder || '— Chọn —' });
        }
    };

    requestAnimationFrame(tryInitAll);

    document.querySelectorAll('[role="tab"]').forEach((btn) => {
        btn.addEventListener('click', () => requestAnimationFrame(tryInitAll));
    });

    form.querySelector('[name="listing_type"]')?.addEventListener('change', () => requestAnimationFrame(tryInitAll));
    form.querySelector('#ts-property_type')?.addEventListener('change', () => requestAnimationFrame(tryInitAll));
}

/** True nếu bất kỳ tổ tiên nào có x-show đang ẩn (display:none) — panel tab HOẶC section con. */
function _isHiddenByAncestor(el) {
    let node = el.parentElement;
    while (node) {
        if (node.hasAttribute('x-show') && node.style.display === 'none') return true;
        node = node.parentElement;
    }
    return false;
}

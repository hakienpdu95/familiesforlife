/**
 * resources/js/modules/tom-select.js
 * Tom Select v2 wrapper — searchable select/autocomplete
 *
 * Blade:  @vite(['resources/js/modules/tom-select.js'])
 * Use:    initTomSelect('#mySelect', { ...options })
 *         initTagsInput('#myInput', options)
 */
import TomSelect from 'tom-select';
import 'tom-select/dist/css/tom-select.css';

const DEFAULTS = {
    create:          false,
    sortField:       { field: 'text', direction: 'asc' },
    placeholder:     'Chọn...',
    searchField:     ['text', 'value'],
    maxOptions:      200,
    // Append dropdown to body → thoát khỏi overflow/clip của parent container
    // (cần thiết khi TomSelect nằm trong Alpine x-show, modal, tab ẩn, v.v.)
    dropdownParent:  'body',
    render: {
        no_results: () => `<div class="no-results" style="padding:8px 12px;font-size:13px;color:#94a3b8">Không tìm thấy kết quả</div>`,
    },
};

const TomSelectInstances = new Map();
window.TomSelectInstances = TomSelectInstances;

function initTomSelect(selector, options = {}) {
    const el = typeof selector === 'string'
        ? document.querySelector(selector)
        : selector;
    if (!el) { console.warn('[TomSelect] Element not found:', selector); return null; }

    const ts = new TomSelect(el, { ...DEFAULTS, ...options });
    TomSelectInstances.set(typeof selector === 'string' ? selector : el.id, ts);
    return ts;
}

/** Tags mode: nhập text → tạo tag */
function initTagsInput(selector, options = {}) {
    return initTomSelect(selector, {
        create:      true,
        createOnBlur:true,
        delimiter:   ',',
        persist:     false,
        placeholder: 'Nhập và Enter để thêm tag...',
        ...options,
    });
}

window.initTomSelect  = initTomSelect;
window.initTagsInput  = initTagsInput;
window.TomSelect      = TomSelect;

/**
 * Province → Ward cascading selects.
 *
 * @param {string} provId     - ID of the province <select>
 * @param {string} wardId     - ID of the ward <select>
 * @param {string} initProv   - pre-selected province_code (edit form / old())
 * @param {string} initWard   - pre-selected ward_code (edit form / old())
 */
function initOrgAddress(provId, wardId, initProv, initWard) {
    const provEl = document.getElementById(provId);
    const wardEl = document.getElementById(wardId);
    if (!provEl || !wardEl) return;

    let pendingWard = initWard || null;

    // Ward: starts disabled, populated on province change
    const wardTs = new TomSelect(wardEl, {
        ...DEFAULTS,
        placeholder: 'Chọn tỉnh / TP trước',
        maxOptions: null,
        plugins: ['clear_button'],
        onChange() {
            // Dispatch native change so form-validation.js có thể clear/re-validate
            wardEl.dispatchEvent(new Event('change', { bubbles: true }));
        },
    });
    wardTs.disable();

    // Province: full searchable list, triggers ward load on change
    const provTs = new TomSelect(provEl, {
        ...DEFAULTS,
        placeholder: 'Tìm tỉnh / thành phố...',
        maxOptions: null,
        plugins: ['clear_button'],
        onChange(val) {
            provEl.dispatchEvent(new Event('change', { bubbles: true }));
            loadWards(val);
        },
    });

    function setWardPlaceholder(text) {
        wardTs.settings.placeholder = text;
        wardTs.control_input.placeholder = text;
    }

    async function loadWards(code) {
        wardTs.clear(true);
        wardTs.clearOptions();
        wardTs.disable();

        if (!code) {
            setWardPlaceholder('Chọn tỉnh / TP trước');
            return;
        }

        setWardPlaceholder('Đang tải...');

        try {
            const res   = await fetch('/api/provinces/' + code + '/wards');
            const wards = await res.json();

            wards.forEach(w => wardTs.addOption({ value: w.ward_code, text: w.name }));
            setWardPlaceholder('Tìm phường / xã...');
            wardTs.enable();

            if (pendingWard) {
                wardTs.setValue(pendingWard, true);
                pendingWard = null;
            }
        } catch (err) {
            console.error('[orgAddress] Lỗi tải phường/xã:', err);
            setWardPlaceholder('Lỗi tải dữ liệu');
            wardTs.enable();
        }
    }

    // Pre-load wards if province already set (edit form)
    if (initProv) loadWards(initProv);
}

window.initOrgAddress = initOrgAddress;

export { initTomSelect, initTagsInput, initOrgAddress, TomSelectInstances };
export default TomSelect;

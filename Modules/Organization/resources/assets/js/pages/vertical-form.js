/**
 * pages/vertical-form.js
 *
 * Form "Tạo vertical từ đầu" của tổ chức (organizations.verticals.create/store —
 * data-vertical-template-form). Chuyển từ Modules/Deployment/resources/assets/js/deployment.js
 * khi module Deployment bị xoá — logic này không liên quan gì tới Deployment, chỉ tình cờ
 * sống chung file trước đây.
 *
 * Responsibilities:
 *   1. Code auto-fill từ label (Vietnamese-aware slug)
 *   2. Default roles — tag input tự do (window.initTagsInput, từ tom-select.js)
 *   3. Survey select (readiness/data-collection template slug) — nạp options theo tổ chức
 */

import { initAllTomSelects } from '@shared/tom-select-factory.js';

const VERTICAL_TEMPLATE_FORM_SEL = '[data-vertical-template-form]';

/** Bảng chuyển ký tự tiếng Việt → Latin cho auto-fill mã (code) từ tên (label). */
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

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(VERTICAL_TEMPLATE_FORM_SEL);
    if (!form) return;

    initFormValidation(VERTICAL_TEMPLATE_FORM_SEL);
    // #ts-organization (select.ts-init) — #ts-default-roles không có class ts-init nên không bị ảnh hưởng
    initAllTomSelects(form);
    _setupCodeAutoFill(form);
    _setupDefaultRolesTags(form);
    _initVerticalTemplateSurveySelects(form);
});

/**
 * Tự động điền mã (code) khi user gõ tên (label) — chỉ trên form tạo mới
 * (field code không tồn tại hoặc bị readonly trên form sửa → không auto-fill).
 * Dừng auto-fill ngay khi user tự chỉnh code (locked = true).
 */
function _setupCodeAutoFill(form) {
    const labelInput = form.querySelector('[name="label"]');
    const codeInput  = form.querySelector('[name="code"]:not([readonly])');
    if (!labelInput || !codeInput) return;

    let locked = codeInput.value.trim() !== '';

    codeInput.addEventListener('input', () => {
        locked = codeInput.value.trim() !== '';
    }, { passive: true });

    codeInput.addEventListener('change', () => {
        if (!codeInput.value.trim()) locked = false;
    }, { passive: true });

    labelInput.addEventListener('input', () => {
        if (locked) return;
        codeInput.value = _toSlug(labelInput.value);
    }, { passive: true });
}

/** Chuyển chuỗi tiếng Việt sang dạng mã url-safe: lowercase, bỏ dấu, chỉ giữ a-z0-9-. */
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

/**
 * Slug khảo sát sẵn sàng / thu thập dữ liệu (#ts-readiness-slug, #ts-data-collection-slug)
 * trên form vertical-templates — nạp options từ surveys theo tổ chức đã chọn.
 * KHÔNG tự nạp danh sách toàn hệ thống khi chưa có tổ chức nào được chọn — chỉ nạp
 * khi có hành vi chọn tổ chức (đổi #ts-organization ở trang create, hoặc tổ chức đã
 * cố định sẵn của bản ghi ở trang edit).
 */
function _initVerticalTemplateSurveySelects(form) {
    const readinessEl      = form.querySelector('#ts-readiness-slug');
    const dataCollectionEl = form.querySelector('#ts-data-collection-slug');
    const tsReadiness      = readinessEl?.tomselect;
    const tsDataCollection = dataCollectionEl?.tomselect;
    if (!tsReadiness && !tsDataCollection) return;

    const apiUrl = readinessEl?.dataset.surveyOptionsApi || dataCollectionEl?.dataset.surveyOptionsApi;
    if (!apiUrl) return;

    const loadBoth = (orgId) => {
        if (tsReadiness) {
            _loadSurveyOptions(apiUrl, tsReadiness, orgId, readinessEl.dataset.selectedValue || '');
        }
        if (tsDataCollection) {
            _loadSurveyOptions(apiUrl, tsDataCollection, orgId, dataCollectionEl.dataset.selectedValue || '');
        }
    };

    const orgEl = form.querySelector('#ts-organization');
    if (orgEl) {
        // Trang create: nếu form re-render sau lỗi validate và đã có tổ chức chọn sẵn (old()) thì
        // nạp lại đúng lựa chọn đó; nếu chưa chọn gì thì để trống, không tự nạp toàn hệ thống.
        const initialOrgId = orgEl.tomselect?.getValue() ?? '';
        if (initialOrgId) loadBoth(initialOrgId);

        orgEl.tomselect?.on('change', (orgId) => {
            tsReadiness?.clear(true);
            tsReadiness?.clearOptions();
            tsDataCollection?.clear(true);
            tsDataCollection?.clearOptions();
            if (orgId) loadBoth(orgId);   // chỉ nạp khi user thực sự chọn 1 tổ chức
        });
    } else if (form.dataset.lockedOrgId) {
        // Trang edit: chỉ nạp khi bản ghi đã thuộc về 1 tổ chức cụ thể — mẫu thư viện
        // dùng chung (organization_id null) thì không có "tổ chức đã chọn" để nạp theo.
        loadBoth(form.dataset.lockedOrgId);
    }
}

function _loadSurveyOptions(apiUrl, tsInstance, orgId, pendingValue) {
    tsInstance.disable();
    fetch(`${apiUrl}?organization_id=${encodeURIComponent(orgId || '')}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
    })
        .then(r => r.ok ? r.json() : [])
        .then(items => {
            tsInstance.clearOptions();
            tsInstance.addOptions(items.map(i => ({ value: i.id, text: i.text })));
            tsInstance.enable();
            if (pendingValue) tsInstance.setValue(pendingValue, true);
        })
        .catch(() => tsInstance.enable());
}

/** Vai trò mặc định (default_roles) — tag input tự do, không có danh sách cố định. */
function _setupDefaultRolesTags(form) {
    const el = form.querySelector('#ts-default-roles');
    if (!el || el.tomselect || typeof window.initTagsInput !== 'function') return;
    window.initTagsInput(el, { placeholder: 'VD: pm, surveyor, data_ops — Enter để thêm' });
}

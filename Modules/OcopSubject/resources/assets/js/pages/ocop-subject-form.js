import { initAllTomSelects } from '@shared/tom-select-factory.js';

const FORM_SEL = '[data-ocop-subject-form]';
const RE_TAB_XSHOW = /tab\s*===\s*['"](\w+)['"]/;
const GALLERY_COLLECTION = 'ocop_subject_gallery';
const MAX_IMAGES = 10;

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    setupTabGuard(form);
    initAllTomSelects(form);
    initGallery();
});

function initGallery() {
    const el = document.getElementById('ocop-subject-gallery-filepond');
    if (!el || !window.initFilePondUpload) return;

    if (el.dataset.contextId) {
        initFilePondUpload(el, {
            collection: GALLERY_COLLECTION,
            contextType: el.dataset.contextType,
            contextId: el.dataset.contextId,
            maxFiles: Number(el.dataset.maxFiles),
            allowRevert: true,
        });
        return;
    }

    initFilePondUpload(el, {
        collection: GALLERY_COLLECTION,
        maxFiles: MAX_IMAGES,
        bindTo: '#ocop-subject-gallery-media-uuids',
    });
}

function setupTabGuard(form) {
    form.addEventListener('submit', (e) => {
        const missing = collectHiddenErrors(form);
        if (!missing.size) return;

        e.preventDefault();

        const wrapper = form.querySelector('[x-data]');
        try {
            const data = window.Alpine?.$data(wrapper);
            if (data?.tab !== undefined) data.tab = missing.keys().next().value;
        } catch { }

        if (window.Toast) {
            const lines = Array.from(missing.values(), ({ label, fields }) => `${label}: ${fields.join(', ')}`);
            Toast.warning(`Còn thiếu thông tin bắt buộc:\n${lines.join('\n')}`, { duration: 5000 });
        }
    }, true);
}

function collectHiddenErrors(form) {
    const map = new Map();

    for (const field of form.querySelectorAll('[data-req]')) {
        if (field.value.trim()) continue;

        const panel = field.closest('[data-tab-label]');
        if (!panel || panel.style.display !== 'none') continue;

        const tabKey = RE_TAB_XSHOW.exec(panel.getAttribute('x-show') ?? '')?.[1];
        if (!tabKey) continue;

        if (!map.has(tabKey)) map.set(tabKey, { label: panel.dataset.tabLabel ?? tabKey, fields: [] });

        const label = field.closest('.form-control')?.querySelector('.label-text')?.textContent.replace(/\s*\*\s*$/, '').trim();
        map.get(tabKey).fields.push(label || field.name);
    }

    return map;
}

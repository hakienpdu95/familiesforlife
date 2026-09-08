/**
 * pages/ideation-form.js
 *
 * Responsibilities:
 *   1. Inline validation      — delegate to global initFormValidation
 *   2. TomSelect init         — post_category_uuid (ts-init select via initAllTomSelects)
 *   3. Category foundation hint — fetch on category change, show ICP/product summary
 */

import { initAllTomSelects } from '@shared/tom-select-factory.js';

// ── Constants ──────────────────────────────────────────────────────────────

const FORM_SEL = '[data-bulk-ideation-form]';

// ── Entry point ────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    initAllTomSelects(form);
    _setupCategoryFoundationHint(form);
});

// ── Category foundation hint ─────────────────────────────────────────────

function _setupCategoryFoundationHint(form) {
    const select = form.querySelector('[name="post_category_uuid"]');
    const hint = form.querySelector('#category-foundation-hint');
    if (!select || !hint) return;

    const urlTemplate = select.dataset.detailUrlTemplate;
    if (!urlTemplate) return;

    const applyCategory = async (uuid) => {
        if (!uuid) {
            hint.hidden = true;
            hint.textContent = '';
            return;
        }

        hint.hidden = false;
        hint.textContent = 'Đang tải ngữ cảnh chuyên mục...';

        try {
            const res = await fetch(urlTemplate.replace('__UUID__', uuid), {
                headers: { Accept: 'application/json' },
            });
            const data = await res.json().catch(() => ({}));
            const foundation = data.foundation ?? null;

            const parts = [];
            if (foundation?.audience) parts.push(`ICP: ${foundation.audience}`);
            if (foundation?.product_service_docs) parts.push('Đã có tài liệu sản phẩm/dịch vụ trọng tâm');

            hint.textContent = parts.length ? parts.join(' — ') : 'Chuyên mục này chưa có ngữ cảnh biên tập.';
        } catch (e) {
            console.error('[bulk-ideation-prompt-studio] failed to load category foundation detail', e);
            hint.hidden = true;
        }
    };

    if (select.tomselect) {
        select.tomselect.on('change', applyCategory);
    }

    if (select.value) applyCategory(select.value);
}

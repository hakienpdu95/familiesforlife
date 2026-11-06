import { createTs } from '@shared/tom-select-factory.js';

document.addEventListener('DOMContentLoaded', () => {
    const roleEl = document.getElementById('ts-account-form-role');
    if (!roleEl) return;

    createTs(roleEl, {
        placeholder: roleEl.dataset.tsPlaceholder,
        plugins: [],
        allowEmptyOption: false,
    });
});

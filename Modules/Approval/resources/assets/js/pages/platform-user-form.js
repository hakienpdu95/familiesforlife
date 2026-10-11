import { createTs } from '@shared/tom-select-factory.js';

document.addEventListener('DOMContentLoaded', () => {
    const roleEl = document.getElementById('ts-platform-user-form-role');
    if (!roleEl) return;

    createTs(roleEl, {
        placeholder: roleEl.dataset.tsPlaceholder,
        plugins: [],
        allowEmptyOption: false,
    });
});

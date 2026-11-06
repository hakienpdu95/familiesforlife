import { createTs } from '@shared/tom-select-factory.js';

function initRole() {
    const roleEl = document.getElementById('ts-account-form-role');
    if (!roleEl) return;

    createTs(roleEl, {
        placeholder: roleEl.dataset.tsPlaceholder,
        plugins: [],
        allowEmptyOption: false,
    });
}

function initUsername() {
    const usernameEl = document.getElementById('account-username');
    if (!usernameEl) return;

    usernameEl.addEventListener('input', () => {
        const caret = usernameEl.selectionStart;
        usernameEl.value = usernameEl.value.toLowerCase().replace(/\s+/g, '');
        usernameEl.setSelectionRange(caret, caret);
        usernameEl.dataset.touched = usernameEl.value ? '1' : '';
    });

    const emailEl = document.querySelector('#account-email[name=email]');
    if (!emailEl) return;

    let timer = null;
    let controller = null;

    const suggest = () => {
        const email = emailEl.value.trim();
        if (usernameEl.dataset.touched || !email.includes('@') || email.indexOf('@') === 0) return;

        controller?.abort();
        controller = new AbortController();

        const url = new URL(usernameEl.dataset.suggestUrl, window.location.origin);
        url.searchParams.set('seed', email);

        fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal })
            .then((res) => (res.ok ? res.json() : null))
            .then((data) => {
                if (data?.username && !usernameEl.dataset.touched) usernameEl.value = data.username;
            })
            .catch(() => {});
    };

    if (usernameEl.value) usernameEl.dataset.touched = '1';

    emailEl.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(suggest, 400);
    });
    emailEl.addEventListener('change', suggest);
}

document.addEventListener('DOMContentLoaded', () => {
    initRole();
    initUsername();
});

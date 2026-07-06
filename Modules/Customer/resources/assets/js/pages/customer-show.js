/**
 * customer-show.js — Alpine component + helpers cho trang chi tiết khách hàng
 *
 * Context (customer ID, CSRF, URLs) đọc từ data-* attributes
 * trên element [data-customer-show].
 *
 * Exports:
 *   Alpine.data('customerShowPage')          — tab navigation + activity/note submit
 *   window.toggleCustomerNotePin(id, btn)    — POST toggle-pin API
 *   window.deleteCustomerNote(id, btn)       — DELETE note API
 */

function _ctx() {
    const el = document.querySelector('[data-customer-show]');
    return {
        customerId: parseInt(el?.dataset.customerId ?? 0),
        csrf:       el?.dataset.csrf ?? '',
    };
}

function _jsonHeaders(csrf) {
    return {
        'X-CSRF-TOKEN':     csrf,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept':           'application/json',
        'Content-Type':     'application/json',
    };
}

document.addEventListener('alpine:init', () => {
    Alpine.data('customerShowPage', () => {
        const ctx = _ctx();

        return {
            tab:        'info',
            actSaving:  false,
            actError:   '',
            noteSaving: false,
            noteError:  '',

            init() {},

            async submitActivity(e) {
                const form = e.target;
                const body = Object.fromEntries(new FormData(form));

                if (!body.type || !body.title?.trim()) {
                    this.actError = 'Vui lòng điền đầy đủ loại và tiêu đề.';
                    return;
                }

                this.actSaving = true;
                this.actError  = '';

                try {
                    const res  = await fetch(`/customers/${ctx.customerId}/activities`, {
                        method: 'POST',
                        headers: _jsonHeaders(ctx.csrf),
                        body: JSON.stringify(body),
                    });
                    const data = await res.json();

                    if (res.ok && data.ok) {
                        location.reload();
                    } else {
                        this.actError = _flattenErrors(data) || 'Lỗi không xác định.';
                    }
                } catch {
                    this.actError = 'Lỗi kết nối.';
                } finally {
                    this.actSaving = false;
                }
            },

            async submitNote(e) {
                const content = document.getElementById('note-input')?.value.trim();
                if (!content) return;

                this.noteSaving = true;
                this.noteError  = '';

                try {
                    const res  = await fetch(`/customers/${ctx.customerId}/notes`, {
                        method: 'POST',
                        headers: _jsonHeaders(ctx.csrf),
                        body: JSON.stringify({ content }),
                    });
                    const data = await res.json();

                    if (res.ok && data.ok) {
                        location.reload();
                    } else {
                        this.noteError = _flattenErrors(data) || 'Lỗi không xác định.';
                    }
                } catch {
                    this.noteError = 'Lỗi kết nối.';
                } finally {
                    this.noteSaving = false;
                }
            },
        };
    });
});

window.toggleCustomerNotePin = async function (noteId, btn) {
    const ctx = _ctx();
    btn.disabled = true;
    try {
        const res = await fetch(`/customers/${ctx.customerId}/notes/${noteId}/toggle-pin`, {
            method: 'POST',
            headers: _jsonHeaders(ctx.csrf),
        });
        if (res.ok) location.reload();
    } finally {
        btn.disabled = false;
    }
};

window.deleteCustomerNote = async function (noteId, btn) {
    if (!confirm('Xóa ghi chú này?')) return;
    const ctx = _ctx();
    btn.disabled = true;
    try {
        const res = await fetch(`/customers/${ctx.customerId}/notes/${noteId}`, {
            method: 'DELETE',
            headers: _jsonHeaders(ctx.csrf),
        });
        if (res.ok) btn.closest('[data-note-id]')?.remove();
    } finally {
        btn.disabled = false;
    }
};

function _flattenErrors(data) {
    if (data.errors) return Object.values(data.errors).flat().join(' ');
    return data.message ?? '';
}

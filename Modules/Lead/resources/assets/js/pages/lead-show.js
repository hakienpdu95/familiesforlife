/**
 * lead-show.js — Alpine component + helpers cho trang chi tiết cơ hội
 *
 * Tất cả context (lead ID, CSRF, URLs) đọc từ data-* attributes
 * trên element [data-lead-show] — blade không có logic JS inline nào.
 *
 * Exports:
 *   Alpine.data('leadShowPage')           — tab navigation + activity/note submit
 *   window.stageSelectChanged(sel)        — hiện/ẩn note + save button
 *   window.saveStageChange()              — POST change-stage API
 *   window.toggleNotePin(noteId, btn)     — POST toggle-pin API
 *   window.deleteNote(noteId, btn)        — DELETE note API
 *   window.rerunLeadAssessment(btn)       — POST recalculate assessment
 */

// ── Context từ DOM ─────────────────────────────────────────────────────────

function _ctx() {
    const el = document.querySelector('[data-lead-show]');
    return {
        leadId:   parseInt(el?.dataset.leadId   ?? 0),
        csrf:     el?.dataset.csrf               ?? '',
        stageUrl: el?.dataset.changeStageUrl     ?? '',
        origStage: parseInt(el?.dataset.origStage ?? 0),
    };
}

/** Headers chung cho JSON API requests */
function _jsonHeaders(csrf) {
    return {
        'X-CSRF-TOKEN':     csrf,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept':           'application/json',
        'Content-Type':     'application/json',
    };
}

// ── Alpine component ────────────────────────────────────────────────────────

document.addEventListener('alpine:init', () => {
    Alpine.data('leadShowPage', () => {
        const ctx = _ctx();

        return {
            tab:        'activities',
            actSaving:  false,
            actError:   '',
            noteSaving: false,
            noteError:  '',

            init() { /* auto-called by Alpine */ },

            // ── Submit activity ──────────────────────────────────────
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
                    const res  = await fetch(`/api/v1/leads/${ctx.leadId}/activities`, {
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

            // ── Submit note ──────────────────────────────────────────
            async submitNote(e) {
                const content = document.getElementById('note-input')?.value.trim();
                if (!content) return;

                this.noteSaving = true;
                this.noteError  = '';

                try {
                    const res  = await fetch(`/api/v1/leads/${ctx.leadId}/notes`, {
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

// ── Stage change ────────────────────────────────────────────────────────────

window.stageSelectChanged = function (sel) {
    const ctx      = _ctx();
    const newId    = parseInt(sel.value);
    const noteEl   = document.getElementById('stage-note');
    const btnEl    = document.getElementById('stage-save-btn');
    const errEl    = document.getElementById('stage-error');

    const changed = newId !== ctx.origStage;
    noteEl?.classList.toggle('hidden', !changed);
    btnEl?.classList.toggle('hidden',  !changed);
    errEl?.classList.add('hidden');
};

window.saveStageChange = async function () {
    const ctx   = _ctx();
    const sel   = document.getElementById('stage-select');
    const note  = document.getElementById('stage-note');
    const btn   = document.getElementById('stage-save-btn');
    const err   = document.getElementById('stage-error');

    btn.disabled    = true;
    btn.textContent = 'Đang lưu...';
    err?.classList.add('hidden');

    try {
        const res  = await fetch(ctx.stageUrl, {
            method: 'POST',
            headers: _jsonHeaders(ctx.csrf),
            body: JSON.stringify({
                stage_id: parseInt(sel.value),
                note:     note?.value.trim() || null,
            }),
        });
        const data = await res.json();

        if (res.ok && data.ok) {
            location.reload();
        } else {
            if (err) {
                err.textContent = data.message || 'Đổi tình trạng thất bại.';
                err.classList.remove('hidden');
            }
        }
    } catch {
        if (err) {
            err.textContent = 'Lỗi kết nối.';
            err.classList.remove('hidden');
        }
    } finally {
        btn.disabled    = false;
        btn.textContent = 'Lưu thay đổi';
    }
};

// ── Note helpers ────────────────────────────────────────────────────────────

window.toggleNotePin = async function (noteId, btn) {
    const ctx = _ctx();
    btn.disabled = true;
    try {
        const res = await fetch(`/api/v1/leads/${ctx.leadId}/notes/${noteId}/toggle-pin`, {
            method: 'POST',
            headers: _jsonHeaders(ctx.csrf),
        });
        if (res.ok) location.reload();
    } finally {
        btn.disabled = false;
    }
};

window.deleteNote = async function (noteId, btn) {
    if (!confirm('Xóa ghi chú này?')) return;
    const ctx = _ctx();
    btn.disabled = true;
    try {
        const res = await fetch(`/api/v1/leads/${ctx.leadId}/notes/${noteId}`, {
            method: 'DELETE',
            headers: _jsonHeaders(ctx.csrf),
        });
        if (res.ok) btn.closest('[data-note-id]')?.remove();
    } finally {
        btn.disabled = false;
    }
};

// ── Assessment rerun ────────────────────────────────────────────────────────

window.rerunLeadAssessment = async function (btn) {
    if (!confirm('Tính lại đánh giá sâu cho cơ hội này?')) return;
    const ctx  = _ctx();
    const code = btn.dataset.assessmentCode;
    const id   = btn.dataset.assessmentResultId;
    if (!code || !id) return;

    btn.disabled = true;
    try {
        const res  = await fetch(`/dashboard/assessments/${code}/results/${id}/recalculate`, {
            method: 'POST',
            headers: _jsonHeaders(ctx.csrf),
        });
        const data = await res.json();

        if (data.ok) {
            setTimeout(() => location.reload(), 800);
        } else {
            alert(data.message || 'Lỗi khi tính lại.');
            btn.disabled = false;
        }
    } catch {
        alert('Lỗi kết nối.');
        btn.disabled = false;
    }
};

// ── Tag TomSelect ───────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    _initTagSelect();
});

function _initTagSelect() {
    const el = document.getElementById('tag-select');
    if (!el || !window.TomSelect) return;

    const ctx      = _ctx();
    const tagsUrl  = el.dataset.tagsUrl;
    const syncUrl  = el.dataset.syncUrl;
    const errEl    = document.getElementById('tags-error');

    new window.TomSelect('#tag-select', {
        plugins:     ['remove_button'],
        valueField:  'id',
        labelField:  'text',
        searchField: ['text'],
        placeholder: 'Thêm / gỡ tag...',
        create:      false,
        load(query, callback) {
            fetch(`${tagsUrl}?q=${encodeURIComponent(query)}`)
                .then(r => r.json()).then(callback).catch(() => callback());
        },
        render: {
            option: d => `<div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full shrink-0" style="background:${d.color ?? '#6b7280'}"></span>${d.text}</div>`,
            item:   d => `<div class="flex items-center gap-1" style="background:${d.color ?? '#6b7280'};color:#fff;border-radius:4px;padding:0 6px">${d.text}</div>`,
        },
        onChange(values) {
            const ids = values.map(v => parseInt(v)).filter(Boolean);
            _syncTags(ids, ctx.csrf, syncUrl, errEl);
        },
    });
}

async function _syncTags(tagIds, csrf, syncUrl, errEl) {
    errEl?.classList.add('hidden');
    try {
        const res  = await fetch(syncUrl, {
            method: 'PUT',
            headers: _jsonHeaders(csrf),
            body: JSON.stringify({ tag_ids: tagIds }),
        });
        const data = await res.json();

        if (data.ok) {
            const display = document.getElementById('tags-display');
            if (display) {
                if (data.tags?.length) {
                    display.innerHTML = data.tags.map(t => {
                        const span = document.createElement('span');
                        span.className = 'badge badge-sm font-medium text-white';
                        // Only allow safe CSS color values (hex, rgb, named colors)
                        const safeColor = /^(#[0-9a-fA-F]{3,8}|rgb\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*\)|[a-zA-Z]+)$/.test(t.color ?? '') ? t.color : '#6b7280';
                        span.style.background = safeColor;
                        span.textContent = t.name ?? '';
                        return span.outerHTML;
                    }).join('');
                } else {
                    display.innerHTML = '<span class="text-xs text-base-content/40">Chưa có tag</span>';
                }
            }
        } else if (errEl) {
            errEl.textContent = 'Lỗi khi cập nhật tag.';
            errEl.classList.remove('hidden');
        }
    } catch {
        if (errEl) {
            errEl.textContent = 'Lỗi kết nối.';
            errEl.classList.remove('hidden');
        }
    }
}

// ── Utility ─────────────────────────────────────────────────────────────────

function _flattenErrors(data) {
    if (data.errors) return Object.values(data.errors).flat().join(' ');
    return data.message ?? '';
}

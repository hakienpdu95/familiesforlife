/**
 * Modules/AIVideoStudioTemplate/resources/assets/js/aivideostudiotemplate.js
 * Entry point JS module AIVideoStudioTemplate — quản lý Shot inline trên show.blade.php.
 * Build: vite.config.backend.js → public/build/backend/assets/modules/aivideostudiotemplate.[hash].js
 *
 * spec/AIVideoStudioTemplate_Technical_Specification.md §6.1/§6.2 — contract JSON + debounce.
 */
import './pages/project-index.js';

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function csrfToken() {
    return document.querySelector('meta[name=csrf-token]')?.content ?? '';
}

async function fetchJson(url, options = {}) {
    const res = await fetch(url, {
        ...options,
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
            ...(options.body ? { 'Content-Type': 'application/json' } : {}),
            ...(options.headers || {}),
        },
    });

    if (res.status === 204) return null;

    const data = await res.json().catch(() => null);
    if (!res.ok) {
        const err = new Error((data && data.message) || `HTTP ${res.status}`);
        err.status = res.status;
        err.data = data;
        throw err;
    }

    return data;
}

// ── Copy-to-clipboard (readonly textarea) — cùng UX 3 trạng thái với content-outlines.js ──
window.aivsCopy = async function (elId, btnEl) {
    const el = document.getElementById(elId);
    if (!el) return;

    const idleHtml = btnEl ? btnEl.innerHTML : null;
    if (btnEl) {
        btnEl.disabled = true;
        btnEl.innerHTML = '<span class="loading loading-spinner loading-xs"></span> Đang copy...';
    }

    try {
        await navigator.clipboard.writeText(el.value);
        window.Toast?.success('Đã copy vào clipboard.');

        if (btnEl) {
            btnEl.classList.remove('btn-primary', 'btn-outline');
            btnEl.classList.add('btn-success');
            btnEl.innerHTML = '✓ Đã copy!';
            setTimeout(() => {
                btnEl.classList.remove('btn-success');
                btnEl.classList.add('btn-outline');
                btnEl.innerHTML = idleHtml;
                btnEl.disabled = false;
            }, 1500);
        }
    } catch (e) {
        console.error('[aivideostudiotemplate] copy failed', e);
        el.select();
        if (btnEl) { btnEl.innerHTML = idleHtml; btnEl.disabled = false; }
    }
};

// ── Shot card template — dùng cho cả render lần đầu (Blade, xem show.blade.php) lẫn thêm mới ──
function shotFieldsHtml(shot) {
    const fields = [
        ['subject', 'Subject (Chủ thể)', null],
        ['action', 'Action (Hành động)', null],
        ['environment', 'Environment (Bối cảnh)', null],
        ['camera', 'Camera (Góc máy)', null],
        ['style', 'Style (Phong cách)', null],
        ['mood', 'Mood (Tâm trạng)', 'VD: năng lượng cao, trầm lắng, chuyên nghiệp, vui tươi'],
        ['audio_direction', 'Audio (Âm thanh/Nhạc nền)', 'VD: tiếng bước chân trên sỏi, gió thổi xa xa; nhạc: điện tử tối giản, tempo trung bình — KHÁC lời thoại bên dưới'],
        ['script_line', 'Lời thoại (Script line)', null],
        ['cta_text', 'Call-to-action (CTA)', 'VD: "Mua ngay - Giảm 20% hôm nay" hoặc text nút/đếm ngược hiển thị trên màn hình'],
        ['constraints', 'Constraints (Ràng buộc)', 'VD cụ thể (negative prompt): "không motion blur, nét mặt nhân vật, không lens flare" — tránh mô tả chung chung như "không mờ"'],
    ];

    return fields.map(([key, label, placeholder]) => `
        <div class="form-control">
            <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">${esc(label)}</span></label>
            <textarea rows="2" class="aivs-field textarea textarea-bordered textarea-xs w-full" data-field="${key}"
                      placeholder="${esc(placeholder)}">${esc(shot[key])}</textarea>
        </div>
    `).join('');
}

// v1.3 (deepreel.com/blog/ai-video-prompts) — đếm số từ compiled_prompt, cảnh báo nhẹ nếu lệch
// khoảng khuyến nghị 50-150 từ/prompt (không chặn lưu, chỉ gợi ý).
function updateWordCount(card) {
    const el = card.querySelector('.aivs-compiled');
    const label = card.querySelector('.aivs-word-count');
    if (!el || !label) return;

    const words = el.value.trim() ? el.value.trim().split(/\s+/).length : 0;
    label.textContent = `${words} từ`;
    label.classList.toggle('text-warning', words > 0 && (words < 50 || words > 150));
    label.classList.toggle('text-base-content/40', words === 0 || (words >= 50 && words <= 150));
}

function shotCardHtml(shot, projectUuid) {
    return `
    <div class="card bg-base-100 shadow-sm border border-base-200 aivs-shot-card" data-shot-id="${shot.id}" data-shot-uuid="${shot.uuid}" data-project-uuid="${esc(projectUuid)}">
        <div class="card-body space-y-3">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <input type="text" class="aivs-field input input-bordered input-xs font-medium w-56" data-field="label"
                           value="${esc(shot.label)}" placeholder="VD: Shot 1 — Hook">
                    <input type="number" min="1" max="36000" class="aivs-field input input-bordered input-xs w-24" data-field="duration_seconds"
                           value="${esc(shot.duration_seconds)}" placeholder="Giây" title="Thời lượng ước tính (giây)">
                </div>
                <div class="flex items-center gap-1">
                    <span class="aivs-save-status text-xs text-base-content/40"></span>
                    <button type="button" class="btn btn-ghost btn-xs aivs-move-up" title="Lên">↑</button>
                    <button type="button" class="btn btn-ghost btn-xs aivs-move-down" title="Xuống">↓</button>
                    <button type="button" class="btn btn-ghost btn-xs text-error aivs-delete-shot">Xoá</button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                ${shotFieldsHtml(shot)}
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1 flex items-center justify-between">
                    <span class="label-text text-xs font-medium">Compiled prompt (tự sinh)</span>
                    <span class="aivs-word-count text-xs text-base-content/40"></span>
                </label>
                <div class="flex gap-2 items-start">
                    <textarea readonly rows="4" class="aivs-compiled textarea textarea-bordered textarea-xs w-full font-mono">${esc(shot.compiled_prompt)}</textarea>
                    <button type="button" class="btn btn-outline btn-xs shrink-0 aivs-copy-compiled">Copy</button>
                </div>
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Model/Tool đã dùng</span></label>
                <input type="text" class="aivs-field input input-bordered input-xs w-full" data-field="model_tool"
                       value="${esc(shot.model_tool)}" placeholder="VD: Kling 1.6 - motion trung bình">
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Tài liệu tham chiếu bổ sung</span></label>
                <input type="text" class="aivs-field input input-bordered input-xs w-full" data-field="reference_assets"
                       value="${esc(shot.reference_assets)}" placeholder="VD: link video mẫu chuyển động máy, link track nhạc mẫu cho mood">
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Kết quả AI (dán link/text)</span></label>
                <div class="flex gap-2 items-start">
                    <textarea rows="2" class="aivs-ai-result textarea textarea-bordered textarea-xs w-full">${esc(shot.ai_result)}</textarea>
                    <button type="button" class="btn btn-outline btn-xs shrink-0 aivs-save-result">Lưu kết quả</button>
                </div>
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1">
                    <span class="label-text text-xs font-medium">Ghi chú đánh giá (QC)</span>
                    <span class="label-text-alt text-xs text-base-content/40">chủ thể · chuyển động · góc máy · phong cách · artifact</span>
                </label>
                <textarea rows="2" class="aivs-field textarea textarea-bordered textarea-xs w-full" data-field="qc_notes"
                          placeholder="VD: Đúng nhân vật, chuyển động mượt, camera đúng ý, style khớp thương hiệu, không lỗi hình">${esc(shot.qc_notes)}</textarea>
            </div>
        </div>
    </div>`;
}

function applyShotResource(card, shot) {
    card.dataset.shotId = shot.id;
    card.dataset.shotUuid = shot.uuid;
    card.querySelector('.aivs-compiled').value = shot.compiled_prompt || '';
    updateWordCount(card);

    const aiResultEl = card.querySelector('.aivs-ai-result');
    if (aiResultEl && document.activeElement !== aiResultEl) {
        aiResultEl.value = shot.ai_result || '';
    }
}

function setSaveStatus(card, text) {
    const el = card.querySelector('.aivs-save-status');
    if (el) el.textContent = text;
}

function collectShotPayload(card) {
    const payload = {};
    card.querySelectorAll('.aivs-field').forEach((el) => {
        payload[el.dataset.field] = el.value;
    });
    return payload;
}

function bindShotCard(card, shotApiBaseUrl) {
    const shotUrl = () => `${shotApiBaseUrl}/shots/${card.dataset.shotUuid}`;
    let debounceTimer = null;

    card.querySelectorAll('.aivs-field').forEach((el) => {
        el.addEventListener('input', () => {
            setSaveStatus(card, 'Đang gõ...');
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(async () => {
                setSaveStatus(card, 'Đang lưu...');
                try {
                    const shot = await fetchJson(shotUrl(), {
                        method: 'PUT',
                        body: JSON.stringify(collectShotPayload(card)),
                    });
                    applyShotResource(card, shot);
                    setSaveStatus(card, 'Đã lưu');
                    setTimeout(() => setSaveStatus(card, ''), 2000);
                } catch (e) {
                    console.error('[aivideostudiotemplate] save shot failed', e);
                    setSaveStatus(card, 'Lỗi lưu!');
                }
            }, 700);
        });
    });

    card.querySelector('.aivs-copy-compiled')?.addEventListener('click', function () {
        const ta = card.querySelector('.aivs-compiled');
        const tmpId = 'aivs-compiled-' + card.dataset.shotUuid;
        ta.id = tmpId;
        window.aivsCopy(tmpId, this);
    });

    card.querySelector('.aivs-save-result')?.addEventListener('click', async function () {
        const btn = this;
        const value = card.querySelector('.aivs-ai-result').value;
        btn.disabled = true;
        try {
            await fetchJson(`${shotUrl()}/result`, {
                method: 'PUT',
                body: JSON.stringify({ ai_result: value }),
            });
            window.Toast?.success('Đã lưu kết quả AI.');
        } catch (e) {
            console.error('[aivideostudiotemplate] save result failed', e);
            alert('Lưu kết quả thất bại. Vui lòng thử lại.');
        } finally {
            btn.disabled = false;
        }
    });

    card.querySelector('.aivs-delete-shot')?.addEventListener('click', async function () {
        const label = card.querySelector('[data-field="label"]')?.value || '(chưa đặt tên)';
        if (!window.confirm(`Xoá shot "${label}"? Không thể hoàn tác.`)) return;

        try {
            await fetchJson(shotUrl(), { method: 'DELETE' });
            card.remove();
        } catch (e) {
            console.error('[aivideostudiotemplate] delete shot failed', e);
            alert('Xoá shot thất bại. Vui lòng thử lại.');
        }
    });

    card.querySelector('.aivs-move-up')?.addEventListener('click', () => moveShot(card, -1));
    card.querySelector('.aivs-move-down')?.addEventListener('click', () => moveShot(card, 1));
}

async function moveShot(card, direction) {
    const list = card.parentElement;
    const cards = Array.from(list.querySelectorAll('.aivs-shot-card'));
    const index = cards.indexOf(card);
    const targetIndex = index + direction;
    if (targetIndex < 0 || targetIndex >= cards.length) return;

    if (direction < 0) {
        list.insertBefore(card, cards[targetIndex]);
    } else {
        list.insertBefore(cards[targetIndex], card);
    }

    const projectUuid = card.dataset.projectUuid;
    const shotApiBaseUrl = window.aivsApiBaseUrl;
    const shotIds = Array.from(list.querySelectorAll('.aivs-shot-card')).map((c) => Number(c.dataset.shotId));

    try {
        await fetchJson(`${shotApiBaseUrl}/projects/${projectUuid}/shots/reorder`, {
            method: 'POST',
            body: JSON.stringify({ shot_ids: shotIds }),
        });
    } catch (e) {
        console.error('[aivideostudiotemplate] reorder failed', e);
        alert('Sắp xếp lại thất bại. Vui lòng tải lại trang.');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('aivsShotList');
    if (!list) return;

    const shotApiBaseUrl = window.aivsApiBaseUrl;
    const projectUuid = list.dataset.projectUuid;

    list.querySelectorAll('.aivs-shot-card').forEach((card) => {
        bindShotCard(card, shotApiBaseUrl);
        updateWordCount(card);
    });

    document.getElementById('aivsAddShotBtn')?.addEventListener('click', async function () {
        const btn = this;
        btn.disabled = true;
        try {
            const shot = await fetchJson(`${shotApiBaseUrl}/projects/${projectUuid}/shots`, {
                method: 'POST',
                body: JSON.stringify({}),
            });

            const wrapper = document.createElement('div');
            wrapper.innerHTML = shotCardHtml(shot, projectUuid);
            const card = wrapper.firstElementChild;
            list.appendChild(card);
            bindShotCard(card, shotApiBaseUrl);
            card.querySelector('[data-field="label"]')?.focus();
        } catch (e) {
            console.error('[aivideostudiotemplate] add shot failed', e);
            alert('Thêm shot thất bại. Vui lòng thử lại.');
        } finally {
            btn.disabled = false;
        }
    });
});

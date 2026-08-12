/**
 * Modules/AIVideoStudioTemplate/resources/assets/js/aivideostudiotemplate.js
 * Entry point JS module AIVideoStudioTemplate — quản lý Shot inline trên show.blade.php.
 * Build: vite.config.backend.js → public/build/backend/assets/modules/aivideostudiotemplate.[hash].js
 *
 * spec/AIVideoStudioTemplate_Technical_Specification.md §6.1/§6.2 — contract JSON + debounce.
 *
 * UI/UX v2 (content24.ai "how to create engaging AI videos for marketing") — thêm 3 khối tương tác
 * mới, tất cả tái dùng API JSON đã có sẵn (KHÔNG endpoint mới): `renderTimeline()` (vẽ lại timeline
 * trực quan từ DOM hiện tại, không cần tải lại trang), `renumberShots()`/`updateEmptyState()` (giữ số
 * thứ tự "Cảnh N" + trạng thái rỗng đúng sau add/xoá/sắp xếp lại), và nút "Chèn 5 cảnh mẫu" (orchestrate
 * `POST shots` + `PUT shots/{shot}` 5 lần liên tiếp theo đúng khung Hook/Problem/Solution/Proof/CTA
 * của nguồn). Cấu trúc HTML của `shotCardHtml()`/`shotFieldsGroupsHtml()` PHẢI khớp với `show.blade.php`
 * (điểm đồng bộ đã có từ trước — xem ghi chú ở đầu hàm).
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
// UI/UX v2 — 3 nhóm field (PHẢI khớp thứ tự/nhãn/placeholder với 3 khối @foreach trong show.blade.php).
function shotFieldsGroupsHtml(shot) {
    const groupA = [
        ['subject', 'Subject (Chủ thể)', null],
        ['action', 'Action (Hành động)', null],
        ['environment', 'Environment (Bối cảnh)', 'VD: hẻm nhỏ về đêm, đồng cỏ rộng mở, studio trong nhà, hang động dưới nước + thời điểm: lúc rạng đông, chiều tà, sáng mùa đông, giờ vàng (golden hour) — mô tả cả không gian VÀ thời điểm'],
    ];
    const groupB = [
        ['camera', 'Camera (Góc máy)', 'VD: cận cảnh (close-up), góc thấp (low angle), máy đẩy chậm vào chủ thể (slow push-in) — cỡ cảnh: toàn/trung/cận/đại cận; chuyển động: lia ngang (pan), tracking, tĩnh (locked-off), cầm tay'],
        ['style', 'Style (Phong cách)', 'VD: phong cách phim tài liệu, ánh sáng chụp sản phẩm, nắng vàng hoàng hôn, ánh trăng lạnh (không chỉ "đẹp") — gọi tên rõ nguồn sáng + thời điểm trong ngày nếu ánh sáng quan trọng với cảnh; hoặc phong cách hoạt hình/nghệ thuật (anime, claymation, tranh màu nước, hiệu ứng VHS cũ) nếu cần bản sắc hình ảnh riêng biệt'],
        ['mood', 'Mood (Tâm trạng)', 'VD: năng lượng cao, trầm lắng, chuyên nghiệp, vui tươi'],
        ['audio_direction', 'Audio (Âm thanh/Nhạc nền)', 'VD: tiếng bước chân trên sỏi, gió thổi xa xa; nhạc: điện tử tối giản, tempo trung bình — KHÁC lời thoại bên dưới'],
    ];

    const fieldHtml = ([key, label, placeholder]) => `
        <div class="form-control">
            <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">${esc(label)}</span></label>
            <textarea rows="2" class="aivs-field textarea textarea-bordered textarea-sm w-full" data-field="${key}"
                      placeholder="${esc(placeholder)}">${esc(shot[key])}</textarea>
        </div>`;

    return `
        <div class="space-y-4">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-base-content/40 mb-1.5">Nội dung cảnh — đang diễn ra chuyện gì</p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">${groupA.map(fieldHtml).join('')}</div>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-base-content/40 mb-1.5">Hình ảnh &amp; Âm thanh — trông và nghe thế nào</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">${groupB.map(fieldHtml).join('')}</div>
            </div>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-base-content/40 mb-1.5">Timeline &amp; Lời thoại — nói gì, khi nào</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="form-control md:col-span-2">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Timeline nội dung (theo giây)</span></label>
                        <textarea rows="2" class="aivs-field textarea textarea-bordered textarea-sm w-full" data-field="timeline_breakdown"
                                  placeholder="VD: 0-5s: hook mở đầu gây chú ý&#10;5-15s: nội dung chính&#10;15-20s: kết + CTA">${esc(shot.timeline_breakdown)}</textarea>
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Lời thoại (Script line)</span></label>
                        <textarea rows="2" class="aivs-field textarea textarea-bordered textarea-sm w-full" data-field="script_line">${esc(shot.script_line)}</textarea>
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Call-to-action (CTA)</span></label>
                        <textarea rows="2" class="aivs-field textarea textarea-bordered textarea-sm w-full" data-field="cta_text"
                                  placeholder='VD: "Mua ngay - Giảm 20% hôm nay" hoặc text nút/đếm ngược hiển thị trên màn hình'>${esc(shot.cta_text)}</textarea>
                    </div>
                </div>
            </div>
            <div class="form-control">
                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Constraints (Ràng buộc)</span></label>
                <textarea rows="2" class="aivs-field textarea textarea-bordered textarea-sm w-full" data-field="constraints"
                          placeholder='VD cụ thể (negative prompt): "không motion blur, nét mặt nhân vật, không lens flare" — tránh mô tả chung chung như "không mờ"'>${esc(shot.constraints)}</textarea>
            </div>
        </div>`;
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
    <div id="shot-${shot.uuid}" class="card bg-base-100 shadow-sm border border-base-200 aivs-shot-card scroll-mt-4" data-shot-id="${shot.id}" data-shot-uuid="${shot.uuid}" data-project-uuid="${esc(projectUuid)}">
        <div class="card-body space-y-3">
            <div class="flex items-center justify-between gap-2 flex-wrap">
                <div class="flex items-center gap-2">
                    <span class="badge badge-neutral badge-sm shrink-0 aivs-shot-number">Cảnh</span>
                    <input type="text" class="aivs-field input input-bordered input-sm font-medium w-56" data-field="label"
                           value="${esc(shot.label)}" placeholder="VD: Shot 1 — Hook">
                    <input type="number" min="1" max="36000" class="aivs-field input input-bordered input-sm w-24" data-field="duration_seconds"
                           value="${esc(shot.duration_seconds)}" placeholder="Giây" title="Thời lượng ước tính (giây)">
                </div>
                <div class="flex items-center gap-1">
                    <button type="button" class="btn btn-primary btn-xs aivs-save-shot">Lưu</button>
                    <span class="aivs-save-status text-xs text-base-content/40"></span>
                    <button type="button" class="btn btn-ghost btn-xs aivs-move-up" title="Lên">↑</button>
                    <button type="button" class="btn btn-ghost btn-xs aivs-move-down" title="Xuống">↓</button>
                    <button type="button" class="btn btn-ghost btn-xs text-error aivs-delete-shot">Xoá</button>
                </div>
            </div>

            ${shotFieldsGroupsHtml(shot)}

            <div class="form-control">
                <label class="label py-0 pb-1 flex items-center justify-between">
                    <span class="label-text text-xs font-medium">Prompt hoàn chỉnh (tự sinh)</span>
                    <span class="aivs-word-count text-xs text-base-content/40"></span>
                </label>
                <div class="flex gap-2 items-start">
                    <textarea readonly rows="4" class="aivs-compiled textarea textarea-bordered textarea-sm w-full font-mono">${esc(shot.compiled_prompt)}</textarea>
                    <button type="button" class="btn btn-outline btn-xs shrink-0 aivs-copy-compiled">Copy</button>
                </div>
            </div>

            <details class="collapse collapse-arrow bg-base-200/40 border border-base-200">
                <summary class="collapse-title text-xs font-medium py-2 min-h-0">Prompt 2 bước — Ảnh + Motion (Image-to-Video)</summary>
                <div class="collapse-content space-y-3">
                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Prompt Ảnh (tạo keyframe)</span></label>
                        <div class="flex gap-2 items-start">
                            <textarea rows="3" class="aivs-field aivs-image-prompt textarea textarea-bordered textarea-xs w-full font-mono" data-field="image_prompt"
                                      placeholder="VD: Midjourney/DALL-E prompt cho khung hình tĩnh — chủ thể, bối cảnh, góc máy, phong cách... --ar 9:16">${esc(shot.image_prompt)}</textarea>
                            <button type="button" class="btn btn-outline btn-xs shrink-0 aivs-copy-image-prompt">Copy</button>
                        </div>
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Prompt Motion (hoạt hình hoá ảnh)</span></label>
                        <div class="flex gap-2 items-start">
                            <textarea rows="3" class="aivs-field aivs-motion-prompt textarea textarea-bordered textarea-xs w-full font-mono" data-field="motion_prompt"
                                      placeholder="VD: RunwayML/Kling prompt cho chuyển động — hành động, chuyển động máy, thời lượng...">${esc(shot.motion_prompt)}</textarea>
                            <button type="button" class="btn btn-outline btn-xs shrink-0 aivs-copy-motion-prompt">Copy</button>
                        </div>
                    </div>
                </div>
            </details>

        </div>
    </div>`;
}

function applyShotResource(card, shot) {
    card.dataset.shotId = shot.id;
    card.dataset.shotUuid = shot.uuid;
    card.querySelector('.aivs-compiled').value = shot.compiled_prompt || '';
    updateWordCount(card);
    // v1.12 — image_prompt/motion_prompt giờ là field nhập tay (.aivs-field), KHÔNG còn tự sinh nên
    // KHÔNG cần đồng bộ lại giá trị ở đây — cùng lý do subject/camera/... cũng không đụng tới.
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

// ── UI/UX v2 — timeline trực quan + số thứ tự "Cảnh N" + trạng thái rỗng, tất cả tính lại từ DOM
// hiện tại (không gọi thêm API) mỗi khi danh sách shot thay đổi. Khớp cấu trúc/class với khối
// `#aivsTimeline` server-render trong show.blade.php — sửa 1 bên nhớ sửa bên còn lại. ──
const AIVS_TIMELINE_PALETTE = [
    'bg-primary/10 border-primary/30',
    'bg-secondary/10 border-secondary/30',
    'bg-accent/10 border-accent/30',
    'bg-info/10 border-info/30',
];

function renderTimeline() {
    const container = document.getElementById('aivsTimeline');
    const list = document.getElementById('aivsShotList');
    if (!container || !list) return;

    const cards = Array.from(list.querySelectorAll('.aivs-shot-card'));
    if (cards.length === 0) {
        container.innerHTML = '';
        return;
    }

    const shots = cards.map((card) => ({
        uuid: card.dataset.shotUuid,
        label: card.querySelector('[data-field="label"]')?.value || '',
        duration: Number(card.querySelector('[data-field="duration_seconds"]')?.value) || 0,
    }));

    if (!shots.some((s) => s.duration > 0)) {
        container.innerHTML = `
            <div class="alert py-2 px-3 text-xs bg-base-200/60 border border-base-200">
                <span>Điền <b>Thời lượng (giây)</b> cho từng cảnh bên dưới để xem timeline trực quan tại đây.</span>
            </div>`;
        return;
    }

    let cursor = 0;
    const total = shots.reduce((sum, s) => sum + s.duration, 0);
    const segments = shots.map((s, i) => {
        const flexWeight = s.duration || 5;
        const start = cursor;
        cursor += flexWeight;
        const color = AIVS_TIMELINE_PALETTE[i % AIVS_TIMELINE_PALETTE.length];
        const timeLabel = s.duration ? `${start}–${start + s.duration}s` : 'chưa có thời lượng';
        return `
            <a href="#shot-${s.uuid}" style="flex: ${flexWeight} 1 0" class="${color} border-t-0 border-b-0 px-2 py-2 text-[11px] leading-tight hover:brightness-95 transition-[filter] min-w-[64px]">
                <div class="font-medium truncate">${i + 1}. ${esc(s.label) || 'Chưa đặt tên'}</div>
                <div class="text-base-content/50">${timeLabel}</div>
            </a>`;
    }).join('');

    container.innerHTML = `
        <div class="flex items-center justify-between mb-1.5">
            <span class="text-xs font-medium text-base-content/60">Timeline video</span>
            <span class="badge badge-neutral badge-sm">Tổng: ${total} giây</span>
        </div>
        <div class="flex w-full rounded-lg overflow-hidden border border-base-200 divide-x divide-base-200">${segments}</div>`;
}

function renumberShots() {
    document.querySelectorAll('#aivsShotList .aivs-shot-card').forEach((card, i) => {
        const badge = card.querySelector('.aivs-shot-number');
        if (badge) badge.textContent = `Cảnh ${i + 1}`;
    });
}

function updateEmptyState() {
    const list = document.getElementById('aivsShotList');
    if (!list) return;
    const count = list.querySelectorAll('.aivs-shot-card').length;

    const empty = document.getElementById('aivsEmptyState');
    if (empty) empty.style.display = count === 0 ? '' : 'none';

    const heading = document.getElementById('aivsShotCountHeading');
    if (heading) heading.textContent = `Danh sách cảnh (${count})`;
}

function refreshOverview() {
    renumberShots();
    updateEmptyState();
    renderTimeline();
}

function bindShotCard(card, shotApiBaseUrl) {
    const shotUrl = () => `${shotApiBaseUrl}/shots/${card.dataset.shotUuid}`;

    // v1.13 (phản hồi người dùng — "hạn chế lưu ajax, mỗi shot bấm lưu khi làm xong") — bỏ debounce
    // PUT tự động mỗi lần gõ (trước đây 700ms/lần); giờ chỉ đánh dấu "chưa lưu" (KHÔNG gọi mạng) +
    // cập nhật timeline trực quan tại chỗ (đọc thẳng từ DOM, cũng KHÔNG gọi mạng) — người dùng chủ
    // động bấm nút "Lưu" để gửi 1 request PUT duy nhất cho MỌI field đã sửa của shot đó.
    card.querySelectorAll('.aivs-field').forEach((el) => {
        el.addEventListener('input', () => {
            card.dataset.dirty = 'true';
            setSaveStatus(card, 'Chưa lưu — bấm "Lưu"');
            renderTimeline();
        });
    });

    card.querySelector('.aivs-save-shot')?.addEventListener('click', async function () {
        const btn = this;
        btn.disabled = true;
        setSaveStatus(card, 'Đang lưu...');
        try {
            const shot = await fetchJson(shotUrl(), {
                method: 'PUT',
                body: JSON.stringify(collectShotPayload(card)),
            });
            applyShotResource(card, shot);
            card.dataset.dirty = 'false';
            setSaveStatus(card, 'Đã lưu');
            setTimeout(() => setSaveStatus(card, ''), 2000);
            renderTimeline();
        } catch (e) {
            console.error('[aivideostudiotemplate] save shot failed', e);
            setSaveStatus(card, 'Lỗi lưu!');
        } finally {
            btn.disabled = false;
        }
    });

    card.querySelector('.aivs-copy-compiled')?.addEventListener('click', function () {
        const ta = card.querySelector('.aivs-compiled');
        const tmpId = 'aivs-compiled-' + card.dataset.shotUuid;
        ta.id = tmpId;
        window.aivsCopy(tmpId, this);
    });

    // v1.10 — cùng pattern copy-to-clipboard với compiled_prompt, cho 2 prompt tách riêng.
    card.querySelector('.aivs-copy-image-prompt')?.addEventListener('click', function () {
        const ta = card.querySelector('.aivs-image-prompt');
        const tmpId = 'aivs-image-prompt-' + card.dataset.shotUuid;
        ta.id = tmpId;
        window.aivsCopy(tmpId, this);
    });

    card.querySelector('.aivs-copy-motion-prompt')?.addEventListener('click', function () {
        const ta = card.querySelector('.aivs-motion-prompt');
        const tmpId = 'aivs-motion-prompt-' + card.dataset.shotUuid;
        ta.id = tmpId;
        window.aivsCopy(tmpId, this);
    });

    card.querySelector('.aivs-delete-shot')?.addEventListener('click', async function () {
        const label = card.querySelector('[data-field="label"]')?.value || '(chưa đặt tên)';
        if (!window.confirm(`Xoá shot "${label}"? Không thể hoàn tác.`)) return;

        try {
            await fetchJson(shotUrl(), { method: 'DELETE' });
            card.remove();
            refreshOverview();
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
    // UI/UX v2 — thứ tự đổi ngay (optimistic), cập nhật số "Cảnh N" + timeline mà không đợi response.
    renumberShots();
    renderTimeline();

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

// UI/UX v2 (content24.ai Bước 2 "Write a 30-60s script") — khung 5 cảnh mẫu cho nút "Chèn 5 cảnh mẫu",
// tổng đúng 60s (3+7+15+15+20) khớp bảng Hook/Problem/Solution/Proof/CTA hiển thị ở khối cheat-sheet.
const AIVS_QUICK_START_TEMPLATE = [
    { label: 'Hook', duration_seconds: 3 },
    { label: 'Vấn đề', duration_seconds: 7 },
    { label: 'Giải pháp', duration_seconds: 15 },
    { label: 'Bằng chứng', duration_seconds: 15 },
    { label: 'CTA', duration_seconds: 20 },
];

// v1.13 — lưới an toàn cho việc bỏ autosave: cảnh báo nếu rời trang khi còn shot "chưa lưu"
// (`card.dataset.dirty === 'true'`, đặt/gỡ ở `bindShotCard()`), tránh mất nội dung vừa gõ.
window.addEventListener('beforeunload', (e) => {
    const hasUnsaved = document.querySelector('.aivs-shot-card[data-dirty="true"]');
    if (hasUnsaved) {
        e.preventDefault();
        e.returnValue = '';
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('aivsShotList');
    if (!list) return;

    const shotApiBaseUrl = window.aivsApiBaseUrl;
    const projectUuid = list.dataset.projectUuid;

    list.querySelectorAll('.aivs-shot-card').forEach((card) => {
        bindShotCard(card, shotApiBaseUrl);
        updateWordCount(card);
    });
    refreshOverview();

    async function addShot(overrides) {
        const shot = await fetchJson(`${shotApiBaseUrl}/projects/${projectUuid}/shots`, {
            method: 'POST',
            body: JSON.stringify({}),
        });

        const finalShot = overrides
            ? await fetchJson(`${shotApiBaseUrl}/shots/${shot.uuid}`, {
                method: 'PUT',
                body: JSON.stringify(overrides),
            })
            : shot;

        const wrapper = document.createElement('div');
        wrapper.innerHTML = shotCardHtml(finalShot, projectUuid);
        const card = wrapper.firstElementChild;
        list.appendChild(card);
        bindShotCard(card, shotApiBaseUrl);
        return card;
    }

    async function handleAddShotClick(btn) {
        btn.disabled = true;
        try {
            const card = await addShot();
            refreshOverview();
            card.querySelector('[data-field="label"]')?.focus();
        } catch (e) {
            console.error('[aivideostudiotemplate] add shot failed', e);
            alert('Thêm shot thất bại. Vui lòng thử lại.');
        } finally {
            btn.disabled = false;
        }
    }

    document.getElementById('aivsAddShotBtn')?.addEventListener('click', function () { handleAddShotClick(this); });
    document.getElementById('aivsAddFirstShotBtn')?.addEventListener('click', function () { handleAddShotClick(this); });

    document.getElementById('aivsQuickStartBtn')?.addEventListener('click', async function () {
        const btn = this;
        btn.disabled = true;
        try {
            for (const step of AIVS_QUICK_START_TEMPLATE) {
                await addShot(step);
            }
            refreshOverview();
            window.Toast?.success('Đã chèn 5 cảnh mẫu — sửa lại nội dung cho phù hợp với sản phẩm của bạn.');
        } catch (e) {
            console.error('[aivideostudiotemplate] quick start failed', e);
            alert('Chèn cảnh mẫu thất bại. Vui lòng thử lại — các cảnh đã tạo thành công vẫn được giữ lại.');
        } finally {
            btn.disabled = false;
        }
    });
});

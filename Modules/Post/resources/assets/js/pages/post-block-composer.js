/**
 * pages/post-block-composer.js
 *
 * Block-composer soạn bài viết (kiểu Gutenberg): dãy block "Đoạn văn bản" (Jodit mini-editor
 * riêng từng block) và "Khối sản phẩm" (chọn sản phẩm + template, hiển thị inline — không qua
 * modal dialog nữa). Thêm/xoá/sắp xếp (nút lên/xuống) trực tiếp trên DOM — KHÔNG re-render lại
 * toàn bộ danh sách từ 1 state trung tâm mỗi lần đổi, để không phá mất nội dung đang gõ dở
 * trong các Jodit instance khác (di chuyển/giữ nguyên DOM node thay vì huỷ-tạo-lại).
 *
 * Requires globals (lazy bundle): initJodit/JoditInstances (jodit.js — phải load TRƯỚC file này).
 * Requires: window.PostExistingBlocks (mảng block hiện có, do Blade @json truyền vào khi sửa bài).
 */
(function () {
    const API_SEARCH = '/api/v1/products/search';
    const MAX_ITEMS = 7;
    const MAX_BUTTONS_PER_ITEM = 5;

    function esc(v) {
        if (v == null) return '';
        return String(v)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function uid(prefix) {
        return prefix + Math.random().toString(36).slice(2, 10);
    }

    async function fetchJson(url) {
        const res = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!res.ok) throw new Error('Request failed: ' + res.status);
        return res.json();
    }

    document.addEventListener('DOMContentLoaded', () => {
        const composerEl = document.querySelector('.pbc-composer');
        if (!composerEl || typeof window.initJodit !== 'function') {
            return;
        }
        initComposer(composerEl);
    });

    function initComposer(composerEl) {
        const listEl = composerEl.querySelector('.pbc-block-list');
        const hiddenInput = composerEl.querySelector('input[name="blocks_json"]');
        const form = composerEl.closest('form');
        let blockSeq = 0;

        function blockHeaderHtml(label, badgeClass) {
            return `<div class="pbc-block-header">
                <span class="${badgeClass}">${esc(label)}</span>
                <span style="margin-left:auto;display:flex;gap:2px;">
                    <button type="button" class="btn btn-ghost btn-xs pbc-up" title="Lên">↑</button>
                    <button type="button" class="btn btn-ghost btn-xs pbc-down" title="Xuống">↓</button>
                    <button type="button" class="btn btn-ghost btn-xs text-error pbc-remove" title="Xoá block">✕</button>
                </span>
            </div>`;
        }

        function wireCommonControls(el) {
            el.querySelector('.pbc-up').addEventListener('click', () => moveBlock(el, -1));
            el.querySelector('.pbc-down').addEventListener('click', () => moveBlock(el, 1));
            el.querySelector('.pbc-remove').addEventListener('click', () => removeBlock(el));
        }

        function moveBlock(el, dir) {
            if (dir === -1 && el.previousElementSibling) {
                listEl.insertBefore(el, el.previousElementSibling);
            } else if (dir === 1 && el.nextElementSibling) {
                listEl.insertBefore(el.nextElementSibling, el);
            }
        }

        function removeBlock(el) {
            if (!confirm('Xoá block này khỏi bài viết?')) return;
            el.remove();
            updateTotalWordCount();
        }

        // ── Đếm từ (Content Marketing 2026-07-28) — bài 2000+ từ có khả năng thành công gấp
        // gần đôi theo nghiên cứu được dẫn; Jodit tắt bộ đếm mặc định toàn app (jodit.js BASE),
        // bật RIÊNG cho block-composer qua override + cộng dồn TOÀN BÀI (khác đếm/instance mặc
        // định của Jodit — nội dung 1 bài giờ tách nhiều block, không phải 1 khung duy nhất).
        // Chỉ cộng dồn block `type=text` (văn xuôi) — không tính FAQ/citation/howto vào đây, vì
        // đó là nội dung có cấu trúc riêng, không so sánh cùng ý nghĩa với "độ dài bài viết".
        const totalWordCountEl = document.querySelector('.pbc-total-word-count');

        function updateTotalWordCount() {
            if (!totalWordCountEl) return;

            let totalWords = 0;
            listEl.querySelectorAll(':scope > .pbc-block[data-kind="text"]').forEach((blockEl) => {
                const html = blockEl._joditEditor ? blockEl._joditEditor.value : '';
                const text = html.replace(/<[^>]*>/g, ' ').replace(/&nbsp;/gi, ' ').trim();
                if (text) totalWords += text.split(/\s+/).filter(Boolean).length;
            });

            totalWordCountEl.textContent = `Tổng ${totalWords.toLocaleString('vi-VN')} từ (đoạn văn bản)`;
        }

        // ── Block: Đoạn văn bản ──────────────────────────────────────────

        function addTextBlock(initialHtml) {
            const blockId = 'txt-' + (blockSeq++);
            const el = document.createElement('div');
            el.className = 'pbc-block';
            el.dataset.kind = 'text';
            el.innerHTML = blockHeaderHtml('Đoạn văn bản', 'pbc-badge-text')
                + '<div class="pbc-block-body"><textarea class="jodit-editor" data-jodit-preset="standard"></textarea></div>';
            wireCommonControls(el);
            listEl.appendChild(el);

            const textarea = el.querySelector('textarea');
            textarea.name = 'block_text_' + blockId;
            textarea.value = initialHtml || '';
            el._joditEditor = window.initJodit(textarea, { showWordsCounter: true, showCharsCounter: true });
            el._joditEditor.events.on('change', () => updateTotalWordCount());
        }

        // ── Block: Khối sản phẩm ─────────────────────────────────────────

        function addProductBlock(existingState) {
            const el = document.createElement('div');
            el.className = 'pbc-block';
            el.dataset.kind = 'product';
            el.innerHTML = blockHeaderHtml('Khối sản phẩm', 'pbc-badge-product')
                + '<div class="pbc-block-body"></div>';
            wireCommonControls(el);
            listEl.appendChild(el);

            const state = existingState || { blockUuid: uid('blk-'), template: 'single_card', heading: '', items: [], blockButtons: [] };
            el._productState = state;
            renderProductForm(el.querySelector('.pbc-block-body'), state);
        }

        function renderProductForm(body, state) {
            body.innerHTML = `
                <div style="display:grid;grid-template-columns:1fr 200px;gap:10px;margin-bottom:10px;">
                    <div>
                        <label style="font-size:11px;font-weight:600;display:block;margin-bottom:2px;">Tiêu đề khối (không bắt buộc)</label>
                        <input type="text" class="input input-bordered input-sm w-full pbc-heading" value="${esc(state.heading)}"
                               placeholder="VD: Sản phẩm gợi ý cho mẹ và bé">
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:600;display:block;margin-bottom:2px;">Mẫu hiển thị</label>
                        <select class="select select-bordered select-sm w-full pbc-template">
                            <option value="single_card">Thẻ đơn (1 sản phẩm)</option>
                            <option value="banner">Banner nổi bật (1 sản phẩm)</option>
                            <option value="multi_grid">Lưới nhiều sản phẩm (2-7)</option>
                            <option value="compact_list">Danh sách gọn (2-7)</option>
                        </select>
                    </div>
                </div>
                <div style="margin-bottom:10px;">
                    <label style="font-size:11px;font-weight:600;display:block;margin-bottom:2px;">Tìm sản phẩm</label>
                    <input type="text" class="input input-bordered input-sm w-full pbc-search" placeholder="Nhập tên sản phẩm...">
                    <div class="pbc-product-search-results" style="margin-top:6px;display:none;"></div>
                </div>
                <div>
                    <div style="font-size:11px;font-weight:600;margin-bottom:4px;">
                        Đã chọn (<span class="pbc-count">${state.items.length}</span>/${MAX_ITEMS})
                    </div>
                    <div class="pbc-selected-list" style="display:flex;flex-direction:column;gap:6px;"></div>
                </div>
            `;

            const templateSelect = body.querySelector('.pbc-template');
            templateSelect.value = state.template;
            templateSelect.addEventListener('change', () => { state.template = templateSelect.value; });

            body.querySelector('.pbc-heading').addEventListener('input', (e) => { state.heading = e.target.value; });

            const searchInput = body.querySelector('.pbc-search');
            const resultsBox = body.querySelector('.pbc-product-search-results');
            const selectedList = body.querySelector('.pbc-selected-list');
            const countLabel = body.querySelector('.pbc-count');

            function refreshSelectedList() {
                countLabel.textContent = state.items.length;
                selectedList.innerHTML = state.items.map((item, idx) => selectedItemHtml(item, idx)).join('');

                selectedList.querySelectorAll('[data-remove-idx]').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        state.items.splice(Number(btn.dataset.removeIdx), 1);
                        refreshSelectedList();
                    });
                });
                selectedList.querySelectorAll('[data-link-toggle]').forEach((chip) => {
                    chip.addEventListener('click', () => {
                        const idx = Number(chip.dataset.itemIdx);
                        toggleProductLinkButton(state.items[idx], chip.dataset.linkToggle);
                        refreshSelectedList();
                    });
                });
            }

            function selectedItemHtml(item, idx) {
                const linkChips = (item.cachedLinks || []).map((l) => {
                    const active = item.buttons.some((b) => b.urlType === 'use_product_link' && b.productLinkType === l.type);
                    return `<span class="pbc-link-chip${active ? ' active' : ''}" data-link-toggle="${esc(l.type)}" data-item-idx="${idx}">
                        ${active ? '✓ ' : ''}${esc(l.label)}</span>`;
                }).join('');

                return `<div class="pbc-selected-item">
                    ${item.cachedImage ? `<img src="${esc(item.cachedImage)}" style="width:36px;height:36px;object-fit:cover;border-radius:6px;flex-shrink:0;">` : ''}
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${esc(item.cachedName || ('#' + item.productId))}</div>
                        <div style="font-size:11px;opacity:.6;">${esc(item.cachedPrice || '')}</div>
                        <div style="margin-top:4px;">${linkChips || '<span style="font-size:11px;opacity:.4;">Sản phẩm chưa cấu hình link nào</span>'}</div>
                    </div>
                    <button type="button" data-remove-idx="${idx}" title="Bỏ chọn"
                        class="btn btn-ghost btn-xs text-error" style="flex-shrink:0;">×</button>
                </div>`;
            }

            let searchTimer;
            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimer);
                const q = searchInput.value.trim();
                searchTimer = setTimeout(() => runSearch(q), 300);
            });

            async function runSearch(keyword) {
                try {
                    const json = await fetchJson(API_SEARCH + '?q=' + encodeURIComponent(keyword) + '&per_page=15');
                    resultsBox.style.display = json.data.length ? 'block' : 'none';
                    resultsBox.innerHTML = json.data.map((p) => resultRowHtml(p)).join('');
                    resultsBox.querySelectorAll('[data-pick-id]').forEach((row) => {
                        row.addEventListener('click', () => pickProduct(json.data.find((p) => String(p.id) === row.dataset.pickId)));
                    });
                } catch (e) {
                    resultsBox.style.display = 'block';
                    resultsBox.innerHTML = '<div style="padding:8px;font-size:12px;color:#ef4444;">Không tải được danh sách sản phẩm.</div>';
                }
            }

            function resultRowHtml(p) {
                const already = state.items.some((it) => it.productId === p.id);
                return `<div class="pbc-product-result-row" data-pick-id="${esc(p.id)}" style="opacity:${already ? '.5' : '1'};cursor:${already ? 'not-allowed' : 'pointer'};">
                    ${p.cover_image_url ? `<img src="${esc(p.cover_image_url)}" style="width:28px;height:28px;object-fit:cover;border-radius:4px;">` : ''}
                    <div style="flex:1;font-size:12px;">${esc(p.name)}</div>
                    <div style="font-size:11px;opacity:.6;">${esc(p.price_label || '')}</div>
                    ${already ? '<span style="font-size:11px;opacity:.4;">Đã chọn</span>' : ''}
                </div>`;
            }

            function pickProduct(p) {
                if (!p || state.items.some((it) => it.productId === p.id)) return;
                if (state.items.length >= MAX_ITEMS) {
                    alert(`Đã đạt tối đa ${MAX_ITEMS} sản phẩm/khối.`);
                    return;
                }

                const item = {
                    itemKey: uid('itm-'),
                    productId: p.id,
                    titleOverride: '', priceLabelOverride: '', descriptionOverride: '', imageUrlOverride: '',
                    buttons: [],
                    cachedName: p.name, cachedImage: p.cover_image_url || '', cachedPrice: p.price_label || '',
                    cachedLinks: p.available_links || [],
                };
                if (item.cachedLinks.length) {
                    toggleProductLinkButton(item, item.cachedLinks[0].type);
                }

                state.items.push(item);

                if (state.items.length === 1 && templateSelect.value === 'multi_grid') templateSelect.value = 'single_card';
                if (state.items.length >= 2 && ['single_card', 'banner'].includes(templateSelect.value)) {
                    templateSelect.value = 'multi_grid';
                    state.template = 'multi_grid';
                }

                refreshSelectedList();
                searchInput.value = '';
                resultsBox.style.display = 'none';
            }

            function toggleProductLinkButton(item, linkType) {
                const idx = item.buttons.findIndex((b) => b.urlType === 'use_product_link' && b.productLinkType === linkType);
                if (idx !== -1) {
                    item.buttons.splice(idx, 1);
                    return;
                }
                if (item.buttons.length >= MAX_BUTTONS_PER_ITEM) {
                    alert(`Tối đa ${MAX_BUTTONS_PER_ITEM} nút/sản phẩm.`);
                    return;
                }
                item.buttons.push({
                    btnKey: uid('btn-'), label: '', urlType: 'use_product_link', url: '',
                    productLinkType: linkType, target: '_blank', style: 'primary',
                });
            }

            refreshSelectedList();
        }

        // ── Block: Câu hỏi thường gặp (FAQ, AEO 2026-07-28) ───────────────
        // Đơn giản hơn nhiều so với Khối sản phẩm: không tham chiếu entity ngoài (không cần
        // search API), không có buttons/CTA, không có template — chỉ danh sách câu hỏi/trả lời.

        function addFaqBlock(existingState) {
            const el = document.createElement('div');
            el.className = 'pbc-block';
            el.dataset.kind = 'faq';
            el.innerHTML = blockHeaderHtml('Câu hỏi thường gặp', 'pbc-badge-faq')
                + '<div class="pbc-block-body"></div>';
            wireCommonControls(el);
            listEl.appendChild(el);

            const state = existingState || { blockUuid: uid('blk-'), heading: '', items: [] };
            el._faqState = state;
            renderFaqForm(el.querySelector('.pbc-block-body'), state);
        }

        function renderFaqForm(body, state) {
            body.innerHTML = `
                <div style="margin-bottom:10px;">
                    <label style="font-size:11px;font-weight:600;display:block;margin-bottom:2px;">Tiêu đề khối (không bắt buộc)</label>
                    <input type="text" class="input input-bordered input-sm w-full pbc-faq-heading" value="${esc(state.heading)}"
                           placeholder="VD: Câu hỏi thường gặp">
                </div>
                <div class="pbc-faq-items" style="display:flex;flex-direction:column;gap:8px;"></div>
                <button type="button" class="btn btn-sm btn-outline pbc-faq-add mt-2">+ Thêm câu hỏi</button>
            `;

            body.querySelector('.pbc-faq-heading').addEventListener('input', (e) => { state.heading = e.target.value; });

            const itemsBox = body.querySelector('.pbc-faq-items');

            function renderItems() {
                itemsBox.innerHTML = state.items.map((item, idx) => `
                    <div class="pbc-faq-item" data-idx="${idx}" style="border:1px solid var(--fallback-b3,#e5e7eb);border-radius:8px;padding:8px;">
                        <div style="display:flex;gap:6px;align-items:start;">
                            <div style="flex:1;">
                                <input type="text" class="input input-bordered input-sm w-full pbc-faq-question" placeholder="Câu hỏi" value="${esc(item.question)}">
                                <textarea class="textarea textarea-bordered textarea-sm w-full mt-1 pbc-faq-answer" rows="2" placeholder="Câu trả lời">${esc(item.answer)}</textarea>
                            </div>
                            <button type="button" class="btn btn-ghost btn-xs text-error pbc-faq-remove" title="Xoá câu hỏi">✕</button>
                        </div>
                    </div>
                `).join('');

                itemsBox.querySelectorAll('.pbc-faq-item').forEach((row) => {
                    const idx = Number(row.dataset.idx);
                    row.querySelector('.pbc-faq-question').addEventListener('input', (e) => { state.items[idx].question = e.target.value; });
                    row.querySelector('.pbc-faq-answer').addEventListener('input', (e) => { state.items[idx].answer = e.target.value; });
                    row.querySelector('.pbc-faq-remove').addEventListener('click', () => {
                        state.items.splice(idx, 1);
                        renderItems();
                    });
                });
            }

            body.querySelector('.pbc-faq-add').addEventListener('click', () => {
                state.items.push({ question: '', answer: '' });
                renderItems();
            });

            renderItems();
        }

        // ── Block: Trích dẫn có nguồn (Citation, GEO đợt 4 2026-07-28) ────
        // Đơn giản nhất trong 5 loại block: KHÔNG có bảng con (không phải danh sách item lặp
        // lại) — 1 khối = 1 câu trích dẫn/thống kê + tên nguồn (bắt buộc) + link nguồn (tuỳ chọn).

        function addCitationBlock(existingState) {
            const el = document.createElement('div');
            el.className = 'pbc-block';
            el.dataset.kind = 'citation';
            el.innerHTML = blockHeaderHtml('Trích dẫn có nguồn', 'pbc-badge-citation')
                + '<div class="pbc-block-body"></div>';
            wireCommonControls(el);
            listEl.appendChild(el);

            const state = existingState || { citationText: '', citationSourceName: '', citationSourceUrl: '' };
            el._citationState = state;
            renderCitationForm(el.querySelector('.pbc-block-body'), state);
        }

        function renderCitationForm(body, state) {
            body.innerHTML = `
                <div style="margin-bottom:10px;">
                    <label style="font-size:11px;font-weight:600;display:block;margin-bottom:2px;">Nội dung trích dẫn/thống kê</label>
                    <textarea class="textarea textarea-bordered textarea-sm w-full pbc-citation-text" rows="2"
                              placeholder="VD: 82% trẻ dưới 2 tuổi ở Việt Nam thiếu vi chất kẽm theo khảo sát quốc gia 2025">${esc(state.citationText)}</textarea>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <div>
                        <label style="font-size:11px;font-weight:600;display:block;margin-bottom:2px;">Tên nguồn (bắt buộc)</label>
                        <input type="text" class="input input-bordered input-sm w-full pbc-citation-source-name" value="${esc(state.citationSourceName)}"
                               placeholder="VD: Bộ Y tế, 2025">
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:600;display:block;margin-bottom:2px;">Link nguồn (không bắt buộc)</label>
                        <input type="url" class="input input-bordered input-sm w-full pbc-citation-source-url" value="${esc(state.citationSourceUrl)}"
                               placeholder="https://...">
                    </div>
                </div>
            `;

            body.querySelector('.pbc-citation-text').addEventListener('input', (e) => { state.citationText = e.target.value; });
            body.querySelector('.pbc-citation-source-name').addEventListener('input', (e) => { state.citationSourceName = e.target.value; });
            body.querySelector('.pbc-citation-source-url').addEventListener('input', (e) => { state.citationSourceUrl = e.target.value; });
        }

        // ── Block: Hướng dẫn từng bước (HowTo, GEO đợt 4 2026-07-28) ──────
        // Cùng độ phức tạp FAQ — danh sách item lặp lại (các bước), không tham chiếu entity ngoài.

        function addHowtoBlock(existingState) {
            const el = document.createElement('div');
            el.className = 'pbc-block';
            el.dataset.kind = 'howto';
            el.innerHTML = blockHeaderHtml('Hướng dẫn từng bước', 'pbc-badge-howto')
                + '<div class="pbc-block-body"></div>';
            wireCommonControls(el);
            listEl.appendChild(el);

            const state = existingState || { blockUuid: uid('blk-'), name: '', description: '', steps: [] };
            el._howtoState = state;
            renderHowtoForm(el.querySelector('.pbc-block-body'), state);
        }

        function renderHowtoForm(body, state) {
            body.innerHTML = `
                <div style="margin-bottom:10px;">
                    <label style="font-size:11px;font-weight:600;display:block;margin-bottom:2px;">Tên hướng dẫn (không bắt buộc)</label>
                    <input type="text" class="input input-bordered input-sm w-full pbc-howto-name" value="${esc(state.name)}"
                           placeholder="VD: Cách pha sữa công thức đúng chuẩn">
                </div>
                <div style="margin-bottom:10px;">
                    <label style="font-size:11px;font-weight:600;display:block;margin-bottom:2px;">Mô tả ngắn (không bắt buộc)</label>
                    <textarea class="textarea textarea-bordered textarea-sm w-full pbc-howto-description" rows="2">${esc(state.description)}</textarea>
                </div>
                <div class="pbc-howto-steps" style="display:flex;flex-direction:column;gap:8px;"></div>
                <button type="button" class="btn btn-sm btn-outline pbc-howto-add mt-2">+ Thêm bước</button>
            `;

            body.querySelector('.pbc-howto-name').addEventListener('input', (e) => { state.name = e.target.value; });
            body.querySelector('.pbc-howto-description').addEventListener('input', (e) => { state.description = e.target.value; });

            const stepsBox = body.querySelector('.pbc-howto-steps');

            function renderSteps() {
                stepsBox.innerHTML = state.steps.map((step, idx) => `
                    <div class="pbc-howto-step" data-idx="${idx}" style="border:1px solid var(--fallback-b3,#e5e7eb);border-radius:8px;padding:8px;">
                        <div style="display:flex;gap:6px;align-items:start;">
                            <div style="flex:1;">
                                <input type="text" class="input input-bordered input-sm w-full pbc-howto-step-name" placeholder="Tên bước (VD: Bước ${idx + 1} — Rửa tay sạch)" value="${esc(step.name)}">
                                <textarea class="textarea textarea-bordered textarea-sm w-full mt-1 pbc-howto-step-text" rows="2" placeholder="Nội dung chi tiết bước này">${esc(step.text)}</textarea>
                            </div>
                            <button type="button" class="btn btn-ghost btn-xs text-error pbc-howto-step-remove" title="Xoá bước">✕</button>
                        </div>
                    </div>
                `).join('');

                stepsBox.querySelectorAll('.pbc-howto-step').forEach((row) => {
                    const idx = Number(row.dataset.idx);
                    row.querySelector('.pbc-howto-step-name').addEventListener('input', (e) => { state.steps[idx].name = e.target.value; });
                    row.querySelector('.pbc-howto-step-text').addEventListener('input', (e) => { state.steps[idx].text = e.target.value; });
                    row.querySelector('.pbc-howto-step-remove').addEventListener('click', () => {
                        state.steps.splice(idx, 1);
                        renderSteps();
                    });
                });
            }

            body.querySelector('.pbc-howto-add').addEventListener('click', () => {
                state.steps.push({ name: '', text: '' });
                renderSteps();
            });

            renderSteps();
        }

        // ── Block: Bảng so sánh (Comparison, GEO đợt 6 2026-08-08) ────────
        // "Comparison fan-out" (spec/giadinh.md) — khác Faq/Howto (1 danh sách phẳng), đây là
        // lưới 2 chiều: cột (đối tượng so sánh) × hàng (tiêu chí). Đổi số CỘT phải đồng bộ độ dài
        // `values` của MỌI hàng (push/splice cùng lúc) — nếu không đồng bộ, submit sẽ bị
        // SyncContentBlocksAction::validateComparisonBlocks() từ chối vì lệch số giá trị/số cột.

        function addComparisonBlock(existingState) {
            const el = document.createElement('div');
            el.className = 'pbc-block';
            el.dataset.kind = 'comparison';
            el.innerHTML = blockHeaderHtml('Bảng so sánh', 'pbc-badge-comparison')
                + '<div class="pbc-block-body"></div>';
            wireCommonControls(el);
            listEl.appendChild(el);

            const state = existingState || {
                blockUuid: uid('blk-'), name: '', description: '',
                columns: [{ label: '' }, { label: '' }],
                rows: [{ label: '', values: ['', ''] }],
            };
            el._comparisonState = state;
            renderComparisonForm(el.querySelector('.pbc-block-body'), state);
        }

        function renderComparisonForm(body, state) {
            body.innerHTML = `
                <div style="margin-bottom:10px;">
                    <label style="font-size:11px;font-weight:600;display:block;margin-bottom:2px;">Tiêu đề bảng (không bắt buộc)</label>
                    <input type="text" class="input input-bordered input-sm w-full pbc-comparison-name" value="${esc(state.name)}"
                           placeholder="VD: So sánh 3 dòng máy hút sữa phổ biến">
                </div>
                <div style="margin-bottom:10px;">
                    <label style="font-size:11px;font-weight:600;display:block;margin-bottom:2px;">Mô tả ngắn (không bắt buộc)</label>
                    <textarea class="textarea textarea-bordered textarea-sm w-full pbc-comparison-description" rows="2">${esc(state.description)}</textarea>
                </div>
                <div class="pbc-comparison-table-wrap" style="overflow-x:auto;"></div>
                <div style="display:flex;gap:8px;margin-top:8px;">
                    <button type="button" class="btn btn-sm btn-outline pbc-comparison-add-column">+ Thêm cột</button>
                    <button type="button" class="btn btn-sm btn-outline pbc-comparison-add-row">+ Thêm tiêu chí</button>
                </div>
            `;

            body.querySelector('.pbc-comparison-name').addEventListener('input', (e) => { state.name = e.target.value; });
            body.querySelector('.pbc-comparison-description').addEventListener('input', (e) => { state.description = e.target.value; });

            const tableWrap = body.querySelector('.pbc-comparison-table-wrap');

            function renderTable() {
                let html = '<table class="pbc-comparison-editor-table" style="border-collapse:collapse;width:100%;">';

                html += '<tr><th style="width:140px;"></th>';
                state.columns.forEach((col, colIdx) => {
                    html += `<th style="padding:4px;">
                        <div style="display:flex;gap:2px;align-items:center;">
                            <input type="text" class="input input-bordered input-sm pbc-comparison-column-label" data-col-idx="${colIdx}"
                                   value="${esc(col.label)}" placeholder="Cột ${colIdx + 1}">
                            <button type="button" class="btn btn-ghost btn-xs text-error pbc-comparison-remove-column" data-col-idx="${colIdx}" title="Xoá cột">✕</button>
                        </div>
                    </th>`;
                });
                html += '</tr>';

                state.rows.forEach((row, rowIdx) => {
                    html += `<tr>
                        <td style="padding:4px;">
                            <div style="display:flex;gap:2px;align-items:center;">
                                <input type="text" class="input input-bordered input-sm pbc-comparison-row-label" data-row-idx="${rowIdx}"
                                       value="${esc(row.label)}" placeholder="Tiêu chí">
                                <button type="button" class="btn btn-ghost btn-xs text-error pbc-comparison-remove-row" data-row-idx="${rowIdx}" title="Xoá tiêu chí">✕</button>
                            </div>
                        </td>`;
                    row.values.forEach((value, colIdx) => {
                        html += `<td style="padding:4px;">
                            <input type="text" class="input input-bordered input-sm pbc-comparison-value" data-row-idx="${rowIdx}" data-col-idx="${colIdx}" value="${esc(value)}">
                        </td>`;
                    });
                    html += '</tr>';
                });

                html += '</table>';
                tableWrap.innerHTML = html;

                tableWrap.querySelectorAll('.pbc-comparison-column-label').forEach((input) => {
                    input.addEventListener('input', (e) => { state.columns[Number(e.target.dataset.colIdx)].label = e.target.value; });
                });
                tableWrap.querySelectorAll('.pbc-comparison-row-label').forEach((input) => {
                    input.addEventListener('input', (e) => { state.rows[Number(e.target.dataset.rowIdx)].label = e.target.value; });
                });
                tableWrap.querySelectorAll('.pbc-comparison-value').forEach((input) => {
                    input.addEventListener('input', (e) => {
                        state.rows[Number(e.target.dataset.rowIdx)].values[Number(e.target.dataset.colIdx)] = e.target.value;
                    });
                });
                tableWrap.querySelectorAll('.pbc-comparison-remove-column').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        if (state.columns.length <= 2) {
                            alert('Bảng so sánh cần tối thiểu 2 cột.');
                            return;
                        }
                        const idx = Number(btn.dataset.colIdx);
                        state.columns.splice(idx, 1);
                        // Đồng bộ MỌI hàng — bỏ đúng giá trị ở vị trí cột vừa xoá (§ đầu section).
                        state.rows.forEach((row) => row.values.splice(idx, 1));
                        renderTable();
                    });
                });
                tableWrap.querySelectorAll('.pbc-comparison-remove-row').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        state.rows.splice(Number(btn.dataset.rowIdx), 1);
                        renderTable();
                    });
                });
            }

            body.querySelector('.pbc-comparison-add-column').addEventListener('click', () => {
                if (state.columns.length >= 6) {
                    alert('Bảng so sánh tối đa 6 cột.');
                    return;
                }
                state.columns.push({ label: '' });
                state.rows.forEach((row) => row.values.push(''));
                renderTable();
            });

            body.querySelector('.pbc-comparison-add-row').addEventListener('click', () => {
                if (state.rows.length >= 20) {
                    alert('Bảng so sánh tối đa 20 tiêu chí.');
                    return;
                }
                state.rows.push({ label: '', values: state.columns.map(() => '') });
                renderTable();
            });

            renderTable();
        }

        // ── Block: Lời chứng thực khách hàng (Testimonial, GEO đợt 7 2026-08-08) ──
        // Cùng độ đơn giản Citation: KHÔNG có bảng con — 1 khối = 1 lời chứng thực + tên người
        // (bắt buộc) + chức danh/công ty/ảnh đại diện/kết quả đạt được (đều tuỳ chọn).

        function addTestimonialBlock(existingState) {
            const el = document.createElement('div');
            el.className = 'pbc-block';
            el.dataset.kind = 'testimonial';
            el.innerHTML = blockHeaderHtml('Lời chứng thực khách hàng', 'pbc-badge-testimonial')
                + '<div class="pbc-block-body"></div>';
            wireCommonControls(el);
            listEl.appendChild(el);

            const state = existingState || {
                quote: '', personName: '', personTitle: '', companyName: '', avatarUrl: '', resultMetric: '',
            };
            el._testimonialState = state;
            renderTestimonialForm(el.querySelector('.pbc-block-body'), state);
        }

        function renderTestimonialForm(body, state) {
            body.innerHTML = `
                <div style="margin-bottom:10px;">
                    <label style="font-size:11px;font-weight:600;display:block;margin-bottom:2px;">Nội dung lời chứng thực</label>
                    <textarea class="textarea textarea-bordered textarea-sm w-full pbc-testimonial-quote" rows="2"
                              placeholder="VD: Nhờ sản phẩm này mà bé nhà mình ăn dặm ngoan hẳn...">${esc(state.quote)}</textarea>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                    <div>
                        <label style="font-size:11px;font-weight:600;display:block;margin-bottom:2px;">Tên người chứng thực (bắt buộc)</label>
                        <input type="text" class="input input-bordered input-sm w-full pbc-testimonial-person-name" value="${esc(state.personName)}"
                               placeholder="VD: Chị Lan">
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:600;display:block;margin-bottom:2px;">Chức danh (không bắt buộc)</label>
                        <input type="text" class="input input-bordered input-sm w-full pbc-testimonial-person-title" value="${esc(state.personTitle)}"
                               placeholder="VD: Mẹ 2 con">
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                    <div>
                        <label style="font-size:11px;font-weight:600;display:block;margin-bottom:2px;">Công ty/thương hiệu (không bắt buộc)</label>
                        <input type="text" class="input input-bordered input-sm w-full pbc-testimonial-company-name" value="${esc(state.companyName)}">
                    </div>
                    <div>
                        <label style="font-size:11px;font-weight:600;display:block;margin-bottom:2px;">Kết quả đạt được (không bắt buộc)</label>
                        <input type="text" class="input input-bordered input-sm w-full pbc-testimonial-result-metric" value="${esc(state.resultMetric)}"
                               placeholder="VD: Tiết kiệm 5 giờ/tuần">
                    </div>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:600;display:block;margin-bottom:2px;">Link ảnh đại diện (không bắt buộc)</label>
                    <input type="url" class="input input-bordered input-sm w-full pbc-testimonial-avatar-url" value="${esc(state.avatarUrl)}"
                           placeholder="https://...">
                </div>
            `;

            body.querySelector('.pbc-testimonial-quote').addEventListener('input', (e) => { state.quote = e.target.value; });
            body.querySelector('.pbc-testimonial-person-name').addEventListener('input', (e) => { state.personName = e.target.value; });
            body.querySelector('.pbc-testimonial-person-title').addEventListener('input', (e) => { state.personTitle = e.target.value; });
            body.querySelector('.pbc-testimonial-company-name').addEventListener('input', (e) => { state.companyName = e.target.value; });
            body.querySelector('.pbc-testimonial-avatar-url').addEventListener('input', (e) => { state.avatarUrl = e.target.value; });
            body.querySelector('.pbc-testimonial-result-metric').addEventListener('input', (e) => { state.resultMetric = e.target.value; });
        }

        // ── Toolbar thêm block ───────────────────────────────────────────

        composerEl.querySelector('.pbc-add-text').addEventListener('click', () => addTextBlock(''));
        composerEl.querySelector('.pbc-add-product').addEventListener('click', () => addProductBlock(null));
        composerEl.querySelector('.pbc-add-faq')?.addEventListener('click', () => addFaqBlock(null));
        composerEl.querySelector('.pbc-add-citation')?.addEventListener('click', () => addCitationBlock(null));
        composerEl.querySelector('.pbc-add-howto')?.addEventListener('click', () => addHowtoBlock(null));
        composerEl.querySelector('.pbc-add-testimonial')?.addEventListener('click', () => addTestimonialBlock(null));
        composerEl.querySelector('.pbc-add-comparison')?.addEventListener('click', () => addComparisonBlock(null));

        // ── Hydrate từ block đã có (trang sửa bài) ──────────────────────

        (window.PostExistingBlocks || []).forEach((b) => {
            if (b.type === 'text') {
                addTextBlock(b.html);
            } else if (b.type === 'product') {
                addProductBlock({
                    blockUuid: b.block_uuid,
                    template: b.template,
                    heading: b.heading || '',
                    items: (b.items || []).map((it) => ({
                        itemKey: it.item_key,
                        productId: it.product_id,
                        titleOverride: it.title_override || '',
                        priceLabelOverride: it.price_label_override || '',
                        descriptionOverride: it.description_override || '',
                        imageUrlOverride: it.image_url_override || '',
                        cachedName: it.cached_name || '',
                        cachedImage: it.cached_image || '',
                        cachedPrice: it.cached_price || '',
                        cachedLinks: it.cached_links || [],
                        buttons: (it.buttons || []).map((btn) => ({
                            btnKey: btn.button_key, label: btn.label || '', urlType: btn.url_type,
                            url: btn.url || '', productLinkType: btn.product_link_type || '',
                            target: btn.target || '_blank', style: btn.style || 'primary',
                        })),
                    })),
                    blockButtons: (b.block_buttons || []).map((btn) => ({
                        btnKey: btn.button_key, label: btn.label || '', urlType: btn.url_type,
                        url: btn.url || '', productLinkType: btn.product_link_type || '',
                        target: btn.target || '_blank', style: btn.style || 'primary',
                    })),
                });
            } else if (b.type === 'faq') {
                addFaqBlock({
                    blockUuid: b.block_uuid,
                    heading: b.heading || '',
                    items: (b.items || []).map((it) => ({ question: it.question || '', answer: it.answer || '' })),
                });
            } else if (b.type === 'citation') {
                addCitationBlock({
                    citationText: b.citation_text || '',
                    citationSourceName: b.citation_source_name || '',
                    citationSourceUrl: b.citation_source_url || '',
                });
            } else if (b.type === 'howto') {
                addHowtoBlock({
                    blockUuid: b.block_uuid,
                    name: b.name || '',
                    description: b.description || '',
                    steps: (b.steps || []).map((s) => ({ name: s.name || '', text: s.text || '' })),
                });
            } else if (b.type === 'comparison') {
                addComparisonBlock({
                    blockUuid: b.block_uuid,
                    name: b.name || '',
                    description: b.description || '',
                    columns: (b.columns || []).map((c) => ({ label: c.label || '' })),
                    rows: (b.rows || []).map((r) => ({ label: r.label || '', values: (r.values || []).map((v) => v || '') })),
                });
            } else if (b.type === 'testimonial') {
                addTestimonialBlock({
                    quote: b.quote || '',
                    personName: b.person_name || '',
                    personTitle: b.person_title || '',
                    companyName: b.company_name || '',
                    avatarUrl: b.avatar_url || '',
                    resultMetric: b.result_metric || '',
                });
            }
        });

        // Set giá trị ban đầu ngay sau hydrate — Jodit không đảm bảo fire 'change' khi set value
        // bằng lập trình (initialHtml ở addTextBlock), chỉ khi user tự gõ, nên phải gọi tường minh
        // 1 lần ở đây để tổng số từ đúng ngay từ lúc mở trang, không phải đợi user gõ phím đầu tiên.
        updateTotalWordCount();

        // ── Serialize khi submit ─────────────────────────────────────────

        form.addEventListener('submit', () => {
            const out = [];

            listEl.querySelectorAll(':scope > .pbc-block').forEach((el) => {
                if (el.dataset.kind === 'text') {
                    out.push({ type: 'text', html: el._joditEditor ? el._joditEditor.value : '' });
                } else if (el.dataset.kind === 'product') {
                    const st = el._productState;
                    out.push({
                        type: 'product',
                        block_uuid: st.blockUuid,
                        template: st.template,
                        heading: st.heading,
                        items: st.items.map((it) => ({
                            item_key: it.itemKey,
                            product_id: it.productId,
                            title_override: it.titleOverride,
                            price_label_override: it.priceLabelOverride,
                            description_override: it.descriptionOverride,
                            image_url_override: it.imageUrlOverride,
                            buttons: it.buttons.map((b) => ({
                                button_key: b.btnKey, label: b.label, url_type: b.urlType,
                                url: b.url, product_link_type: b.productLinkType, target: b.target, style: b.style,
                            })),
                        })),
                        block_buttons: st.blockButtons.map((b) => ({
                            button_key: b.btnKey, label: b.label, url_type: b.urlType,
                            url: b.url, product_link_type: b.productLinkType, target: b.target, style: b.style,
                        })),
                    });
                } else if (el.dataset.kind === 'faq') {
                    const st = el._faqState;
                    out.push({
                        type: 'faq',
                        block_uuid: st.blockUuid,
                        heading: st.heading,
                        items: st.items.map((it) => ({ question: it.question, answer: it.answer })),
                    });
                } else if (el.dataset.kind === 'citation') {
                    const st = el._citationState;
                    out.push({
                        type: 'citation',
                        citation_text: st.citationText,
                        citation_source_name: st.citationSourceName,
                        citation_source_url: st.citationSourceUrl,
                    });
                } else if (el.dataset.kind === 'howto') {
                    const st = el._howtoState;
                    out.push({
                        type: 'howto',
                        block_uuid: st.blockUuid,
                        name: st.name,
                        description: st.description,
                        steps: st.steps.map((s) => ({ name: s.name, text: s.text })),
                    });
                } else if (el.dataset.kind === 'comparison') {
                    const st = el._comparisonState;
                    out.push({
                        type: 'comparison',
                        block_uuid: st.blockUuid,
                        name: st.name,
                        description: st.description,
                        columns: st.columns.map((c) => ({ label: c.label })),
                        rows: st.rows.map((r) => ({ label: r.label, values: r.values })),
                    });
                } else if (el.dataset.kind === 'testimonial') {
                    const st = el._testimonialState;
                    out.push({
                        type: 'testimonial',
                        quote: st.quote,
                        person_name: st.personName,
                        person_title: st.personTitle,
                        company_name: st.companyName,
                        avatar_url: st.avatarUrl,
                        result_metric: st.resultMetric,
                    });
                }
            });

            hiddenInput.value = JSON.stringify(out);
        });
    }
})();

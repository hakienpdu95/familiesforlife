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
            el._joditEditor = window.initJodit(textarea, {});
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

        // ── Toolbar thêm block ───────────────────────────────────────────

        composerEl.querySelector('.pbc-add-text').addEventListener('click', () => addTextBlock(''));
        composerEl.querySelector('.pbc-add-product').addEventListener('click', () => addProductBlock(null));

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
            }
        });

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
                }
            });

            hiddenInput.value = JSON.stringify(out);
        });
    }
})();

/**
 * pages/playlist-edit.js
 * Alpine component cho phần "Nội dung trong playlist" ở dashboard/playlists/items/{playlist}/edit
 * — spec/Playlist_Technical_Specification.md §0/§6.4/§6.7: ô tìm kiếm hợp nhất (trộn Video +
 * PostArticle), thêm/gỡ item qua AJAX, sắp xếp lại bằng nhập tay số thứ tự (không kéo-thả).
 *
 * Server data truyền vào qua x-data="playlistItemsManager({{ Js::from([...]) }})".
 */

function csrfToken() {
    return document.querySelector('meta[name=csrf-token]')?.content ?? '';
}

document.addEventListener('alpine:init', () => {
    Alpine.data('playlistItemsManager', (serverData = {}) => {
        const {
            items: initialItems = [],
            searchUrl = '',
            attachUrl = '',
            reorderUrl = '',
            detachUrlTemplate = '',
        } = serverData;

        let searchAbort = null;

        return {
            items: initialItems,
            search: '',
            results: [],
            searching: false,
            addingKey: null,
            savingOrder: false,

            /**
             * $watch chỉ phản ứng SAU khi đăng ký, và chỉ chạy khi `search` THỰC SỰ đổi (đã qua
             * debounce của x-model ở input) — đây là đường DUY NHẤT kích hoạt tìm kiếm. Cùng lý
             * do đã ghi ở Modules/Video/resources/assets/js/pages/video-index.js: gắn thêm
             * "@input" gọi doSearch() trực tiếp trên input sẽ đọc `this.search` TRƯỚC KHI
             * x-model.debounce kịp gán giá trị mới, khiến tìm kiếm luôn trễ 1 nhịp hoặc đọc
             * chuỗi rỗng — không tìm ra kết quả dù backend trả đúng dữ liệu.
             */
            init() {
                this.$watch('search', () => this.doSearch());
            },

            openPicker() {
                this.search = '';
                this.results = [];
                document.getElementById('playlistItemPickerModal')?.showModal();
            },

            closePicker() {
                document.getElementById('playlistItemPickerModal')?.close();
            },

            async doSearch() {
                const keyword = this.search.trim();

                if (keyword.length < 2) {
                    this.results = [];
                    return;
                }

                // Huỷ request tìm kiếm trước đó nếu còn đang chờ — tránh kết quả cũ (gõ nhanh)
                // ghi đè kết quả mới hơn khi response tới không đúng thứ tự.
                searchAbort?.abort();
                searchAbort = new AbortController();

                this.searching = true;

                try {
                    const url = new URL(searchUrl, window.location.origin);
                    url.searchParams.set('q', keyword);

                    const res = await fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        signal: searchAbort.signal,
                    });

                    const data = await res.json().catch(() => ({}));
                    this.results = res.ok ? (data.data ?? []) : [];
                } catch (e) {
                    if (e.name !== 'AbortError') console.error('[playlist] search failed', e);
                } finally {
                    this.searching = false;
                }
            },

            async addItem(candidate) {
                const key = candidate.type + ':' + candidate.id;
                this.addingKey = key;

                try {
                    const res = await fetch(attachUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN':     csrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept':           'application/json',
                            'Content-Type':     'application/json',
                        },
                        body: JSON.stringify({ itemable_type: candidate.type, itemable_id: candidate.id }),
                    });

                    const data = await res.json().catch(() => ({}));

                    if (!res.ok) {
                        alert(data.message || 'Thêm nội dung thất bại. Vui lòng thử lại.');
                        return;
                    }

                    this.items.push({
                        id: data.item.id,
                        title: data.item.title,
                        typeLabel: data.item.type_label,
                        sortOrder: data.item.sort_order,
                        warning: null,
                    });

                    // Loại khỏi kết quả search hiện tại (đã thêm rồi, tránh bấm "Thêm" 2 lần liên
                    // tiếp trước khi search lại) — không cần gọi lại API.
                    this.results = this.results.filter((r) => !(r.type === candidate.type && r.id === candidate.id));
                } catch (e) {
                    console.error('[playlist] add item failed', e);
                    alert('Lỗi kết nối. Vui lòng thử lại.');
                } finally {
                    this.addingKey = null;
                }
            },

            async removeItem(itemId) {
                if (!confirm('Gỡ nội dung này khỏi playlist?')) return;

                const url = detachUrlTemplate.replace('__ID__', itemId);

                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN':     csrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept':           'application/json',
                            'Content-Type':     'application/x-www-form-urlencoded',
                        },
                        body: '_method=DELETE',
                    });

                    if (!res.ok) {
                        const data = await res.json().catch(() => ({}));
                        alert(data.message || 'Gỡ nội dung thất bại. Vui lòng thử lại.');
                        return;
                    }

                    this.items = this.items.filter((i) => i.id !== itemId);
                } catch (e) {
                    console.error('[playlist] remove item failed', e);
                    alert('Lỗi kết nối. Vui lòng thử lại.');
                }
            },

            /**
             * Sắp theo giá trị "Thứ tự" người dùng đã nhập (tie-break bằng vị trí hiện tại nếu
             * trùng số) rồi gửi mảng ID theo đúng thứ tự đó — ReorderPlaylistItemsAction tự gán
             * lại sort_order tuần tự 1..n, không cần người dùng nhập đúng dãy số liền nhau.
             */
            async saveOrder() {
                this.savingOrder = true;

                const ordered = this.items
                    .map((item, index) => ({ item, index }))
                    .sort((a, b) => (a.item.sortOrder - b.item.sortOrder) || (a.index - b.index))
                    .map(({ item }) => item.id);

                try {
                    const res = await fetch(reorderUrl, {
                        method: 'PATCH',
                        headers: {
                            'X-CSRF-TOKEN':     csrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept':           'application/json',
                            'Content-Type':     'application/json',
                        },
                        body: JSON.stringify({ ordered_item_ids: ordered }),
                    });

                    const data = await res.json().catch(() => ({}));

                    if (!res.ok) {
                        alert(data.message || 'Lưu thứ tự thất bại. Vui lòng thử lại.');
                        return;
                    }

                    // Đồng bộ lại sortOrder hiển thị = vị trí 1..n vừa lưu, khớp với DB.
                    ordered.forEach((id, i) => {
                        const item = this.items.find((it) => it.id === id);
                        if (item) item.sortOrder = i + 1;
                    });
                } catch (e) {
                    console.error('[playlist] save order failed', e);
                    alert('Lỗi kết nối. Vui lòng thử lại.');
                } finally {
                    this.savingOrder = false;
                }
            },
        };
    });
});

/**
 * resources/js/modules/jodit.js
 * Jodit rich-text editor v4 wrapper (~500KB — tải lazy per-page)
 *
 * Quick start (class-based, nhiều field):
 *   <textarea class="jodit-editor" name="description" data-jodit-preset="compact"></textarea>
 *   <script>
 *     document.addEventListener('DOMContentLoaded', () => {
 *       initJoditAll('.jodit-editor');
 *     });
 *   </script>
 *
 * Single field (tuỳ chỉnh sâu):
 *   initJodit('#my-textarea', { height: 350 });
 *
 * ── Presets ────────────────────────────────────────────────────────────
 *   compact   — toolbar nhỏ, height 220  (field phụ: ghi chú, mô tả ngắn)
 *   standard  — toolbar vừa, height 300  (default)
 *   full      — tất cả nút,  height 400  (nội dung chính, SOP, Proposal…)
 *
 * ── Per-field override qua data attribute ─────────────────────────────
 *   data-jodit-preset="compact"               — chọn preset
 *   data-jodit-height="220"                   — ghi đè height (px)
 *   data-jodit-context-type="sop_step"        — context cho orphan tracking
 *   data-jodit-context-id="123"               — context id
 *
 * ── Orphan management ─────────────────────────────────────────────────
 * Khi user upload ảnh vào Jodit, file được lưu tạm (JoditDraft).
 * Module này tự động dọn dẹp:
 *  - Xóa ngay khi user xóa ảnh khỏi nội dung editor
 *  - Xóa khi user rời trang mà không lưu (pagehide + fetch keepalive)
 * Khi form được lưu thành công, gọi clearJoditDraftTracking(editorKey)
 * để tắt cleanup trước khi navigate.
 */
import { Jodit } from 'jodit';
import 'jodit/esm/plugins/all.js';
import 'jodit/es2021/jodit.min.css';
import { Dom } from 'jodit/esm/core/dom/index.js';
import { css } from 'jodit/esm/core/helpers/utils/css.js';
import { hAlignElement } from 'jodit/esm/core/helpers/utils/align.js';
import { isString } from 'jodit/esm/core/helpers/checker/is-string.js';

// ── Draft orphan tracking ──────────────────────────────────────────────
// Map<editorKey, Set<uuid>>  — uuid của ảnh đã upload trong session hiện tại
const _uploadedUuids = new Map();

function _trackUpload(editorKey, uuid) {
    if (!_uploadedUuids.has(editorKey)) _uploadedUuids.set(editorKey, new Set());
    _uploadedUuids.get(editorKey).add(uuid);
}

function _untrack(editorKey, uuid) {
    _uploadedUuids.get(editorKey)?.delete(uuid);
}

/** Gọi sau khi form lưu thành công — dừng cleanup trước khi navigate */
function clearJoditDraftTracking(editorKey) {
    if (editorKey !== undefined) {
        _uploadedUuids.delete(editorKey);
    } else {
        _uploadedUuids.clear();
    }
}

function _csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

/** Xóa ngay 1 draft media — fire-and-forget */
function _deleteMedia(uuid) {
    fetch(`/api/v1/media/jodit-upload/${uuid}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': _csrfToken(), 'Accept': 'application/json' },
    }).catch(() => {});
}

/**
 * Quét content hiện tại, tìm UUID đã upload nhưng không còn trong editor → xóa ngay.
 * Debounced 1.5s để tránh gọi API quá nhiều khi user đang gõ.
 */
function _cleanRemovedImages(editor, editorKey) {
    const tracked = _uploadedUuids.get(editorKey);
    if (!tracked?.size) return;

    const active = new Set();
    const tmp    = document.createElement('div');
    tmp.innerHTML = editor.value;
    tmp.querySelectorAll('img[data-media-uuid]').forEach(img => {
        active.add(img.getAttribute('data-media-uuid'));
    });

    [...tracked].filter(u => !active.has(u)).forEach(uuid => {
        _untrack(editorKey, uuid);
        _deleteMedia(uuid);
    });
}

// ── Discard all remaining drafts on page unload ─────────────────────────
// fetch với keepalive:true được đảm bảo gửi đi dù page unload
// (khác với XHR hay fetch thường bị hủy khi navigate)
window.addEventListener('pagehide', () => {
    const uuids = [];
    _uploadedUuids.forEach(set => uuids.push(...set));
    if (!uuids.length) return;

    fetch('/api/v1/media/jodit-discard', {
        method:    'POST',
        keepalive: true,
        headers: {
            'Content-Type': 'application/json',
            'Accept':        'application/json',
            'X-CSRF-TOKEN':  _csrfToken(),
        },
        body: JSON.stringify({ uuids }),
    }).catch(() => {});
});

// ── Editor config ──────────────────────────────────────────────────────

const BASE_UPLOADER = {
    url:       '/api/v1/media/jodit-upload',
    format:    'json',
    method:    'POST',
    fieldName: 'files[]',
    // Headers intentionally left without CSRF here — populated lazily per-editor
    // in _buildOptions() so long-lived pages always send a fresh token.
    headers:   {},
    isSuccess: (resp) => !resp.error,
    getMsg:    (resp) => resp.message ?? '',
    // Normalize server response: files is [{url, uuid}] — uuid used for orphan tracking
    process: (resp) => ({
        files:   resp.data?.files   ?? [],
        baseurl: resp.data?.baseurl ?? '',
        error:   resp.error   ? 1 : 0,
        message: resp.message ?? '',
    }),
    error(e) {
        console.error('[Jodit] Upload error:', e.message);
    },
};

const BASE = {
    language:             'vi',
    theme:                'default',
    toolbar:              true,
    toolbarInline:        true,
    showCharsCounter:     false,
    showWordsCounter:     false,
    showXPathInStatusbar: false,
    spellcheck:           false,
    imageDefaultWidth:    800,
    removeButtons:        ['about', 'classSpan'],
    uploader:             BASE_UPLOADER,
    popup: {
        img: [
            {
                name: 'delete',
                icon: 'bin',
                tooltip: 'Xóa ảnh',
                exec: (editor, image) => {
                    image && editor.s.removeNode(image);
                },
            },
            '|',
            {
                name: 'pencil',
                exec(editor, current) {
                    if (current?.tagName?.toLowerCase() === 'img') {
                        editor.e.fire('openImageProperties', current);
                    }
                },
                tooltip: 'Chỉnh sửa ảnh',
            },
            '|',
            {
                name: 'valign',
                list: ['Top', 'Middle', 'Bottom', 'Normal'],
                tooltip: 'Căn dọc',
                exec: (editor, image, { control }) => {
                    if (!Dom.isTag(image, 'img')) return;
                    const command = control.args && isString(control.args[0])
                        ? control.args[0].toLowerCase() : '';
                    if (!command) return false;
                    css(image, 'vertical-align', command === 'normal' ? '' : command);
                    editor.e.fire('recalcPositionPopup');
                },
            },
            {
                name: 'left',
                list: ['Left', 'Right', 'Center', 'Normal'],
                tooltip: 'Căn ngang',
                childTemplate: (_, __, value) => value,
                exec: (editor, elm, { control }) => {
                    if (!Dom.isTag(elm, new Set(['img', 'jodit', 'jodit-media']))) return;
                    const command = control.args && isString(control.args[0])
                        ? control.args[0].toLowerCase() : '';
                    if (!command) return false;
                    hAlignElement(elm, command);
                    editor.synchronizeValues();
                    editor.e.fire('recalcPositionPopup');
                },
            },
        ],
    },
};

const PRESETS = {
    compact: {
        height:            220,
        toolbarButtonSize: 'small',
        buttons: [
            'bold', 'italic', 'underline', 'strikethrough', '|',
            'ul', 'ol', '|',
            'paragraph', 'link', '|',
            'undo', 'redo', '|',
            'source',
        ],
        removeButtons: ['about', 'classSpan', 'image'],
    },

    standard: {
        height: 300,
        buttons: [
            'bold', 'italic', 'underline', 'strikethrough', '|',
            'ul', 'ol', '|',
            'font', 'fontsize', 'paragraph', '|',
            'image', 'link', '|',
            'align', '|',
            'undo', 'redo', '|',
            'hr', 'fullsize', 'source',
        ],
        removeButtons: ['about', 'classSpan'],
    },

    full: {
        height:        400,
        removeButtons: ['about', 'classSpan'],
    },
};

const JoditInstances = new Map();
window.JoditInstances = JoditInstances;

/**
 * Build options object với upload handlers aware của editorKey.
 * defaultHandlerSuccess được tạo mới per-editor để capture editorKey trong closure.
 */
function _buildOptions(el, overrides, editorKey) {
    const preset = PRESETS[el.dataset.joditPreset] ?? PRESETS.standard;
    const opts   = { ...BASE, ...preset, ...overrides };

    if (el.dataset.joditHeight) opts.height = Number(el.dataset.joditHeight);

    // Context headers cho orphan tracking (data-jodit-context-type, data-jodit-context-id)
    const ctxHeaders = {};
    if (el.dataset.joditContextType) ctxHeaders['X-Context-Type'] = el.dataset.joditContextType;
    if (el.dataset.joditContextId)   ctxHeaders['X-Context-Id']   = el.dataset.joditContextId;

    opts.uploader = {
        ...opts.uploader,
        // CSRF token read at editor-init time (not at module load) so long-lived
        // pages always send a fresh token. ctxHeaders applied on top.
        headers: {
            'X-CSRF-TOKEN': _csrfToken(),
            ...ctxHeaders,
        },

        // Override success handler: insert img với data-media-uuid, track uuid
        defaultHandlerSuccess(data) {
            data.files.forEach(({ url, uuid }) => {
                if (uuid) _trackUpload(editorKey, uuid);
                // Embed uuid vào img tag để phát hiện removal qua DOM diff
                const html = uuid
                    ? `<img src="${url}" data-media-uuid="${uuid}" />`
                    : `<img src="${url}" />`;
                this.s.insertHTML(html);
            });
        },
    };

    return opts;
}

function initJodit(selector, options = {}) {
    const el = typeof selector === 'string' ? document.querySelector(selector) : selector;
    if (!el) { console.warn('[Jodit] Element not found:', selector); return null; }

    const editorKey = el.id || el.name || `jodit-${JoditInstances.size}`;
    const editor    = Jodit.make(el, _buildOptions(el, options, editorKey));

    // Debounced change listener — detect removed images và xóa ngay
    let changeTimer;
    editor.events.on('change', () => {
        clearTimeout(changeTimer);
        changeTimer = setTimeout(() => _cleanRemovedImages(editor, editorKey), 1500);
    });

    JoditInstances.set(editorKey, editor);
    return editor;
}

/**
 * Khởi tạo tất cả editor theo class selector.
 */
function initJoditAll(selector = '.jodit-editor', shared = {}) {
    document.querySelectorAll(selector).forEach(el => initJodit(el, shared));
}

window.initJodit                 = initJodit;
window.initJoditAll              = initJoditAll;
window.Jodit                     = Jodit;
window.clearJoditDraftTracking   = clearJoditDraftTracking;

export { initJodit, initJoditAll, JoditInstances, clearJoditDraftTracking };
export default Jodit;

/**
 * resources/js/modules/filepond.js
 * FilePond v4 + plugins wrapper
 *
 * Plugins đã đăng ký:
 *  · ImagePreview
 *  · FileValidateSize  (mặc định max 10MB)
 *  · FileRename
 *  · ImageExifOrientation
 *
 * Blade:  @vite(['resources/js/modules/filepond.js'])
 *
 * ── 2 hàm khởi tạo ──────────────────────────────────────────────────────
 *
 * initFilePond(selector, options)
 *   FilePond thuần — không kết nối MediaUploadService.
 *   Dùng khi cần full control qua options.server tự cấu hình.
 *
 * initFilePondUpload(selector, options)
 *   FilePond tích hợp MediaUploadService — dùng cho mọi form upload ảnh/file.
 *   Tự động cấu hình server, CSRF, X-Collection header, MIME/size validation.
 *   Callback: onUploaded(uuid, url, thumbUrl), onReverted(uuid)
 */
import * as FilePond                 from 'filepond';
import 'filepond/dist/filepond.min.css';

import FilePondPluginImagePreview         from 'filepond-plugin-image-preview';
import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css';
import FilePondPluginFileValidateSize     from 'filepond-plugin-file-validate-size';
import FilePondPluginFileRename           from 'filepond-plugin-file-rename';
import FilePondPluginImageExifOrientation from 'filepond-plugin-image-exif-orientation';

FilePond.registerPlugin(
    FilePondPluginImageExifOrientation,
    FilePondPluginImagePreview,
    FilePondPluginFileValidateSize,
    FilePondPluginFileRename,
);

// ── Shared defaults ────────────────────────────────────────────────────

const DEFAULTS = {
    allowMultiple:            true,
    maxFiles:                 10,
    maxFileSize:              '10MB',
    labelIdle:                'Kéo thả file vào đây hoặc <span class="filepond--label-action">Duyệt file</span>',
    labelMaxFileSizeExceeded: 'File quá lớn',
    labelMaxFileSize:         'Kích thước tối đa: {filesize}',
    labelFileTypeNotAllowed:  'Loại file không được hỗ trợ',
    labelFileProcessing:      'Đang tải lên...',
    labelFileProcessingComplete: 'Tải lên hoàn tất',
    labelFileProcessingAborted:  'Đã hủy',
    labelTapToCancel:         'nhấn để hủy',
    labelTapToRetry:          'nhấn để thử lại',
    labelTapToUndo:           'nhấn để xóa',
    labelButtonRemoveItem:    'Xóa',
    labelButtonAbortItemLoad: 'Hủy',
    labelButtonRetryItemLoad: 'Thử lại',
    labelButtonAbortItemProcessing: 'Hủy',
    labelButtonUndoItemProcessing:  'Hoàn tác',
    labelButtonRetryItemProcessing: 'Thử lại',
    labelButtonProcessItem:   'Tải lên',
    imagePreviewMaxHeight:    200,
};

// Collection → client-side maxFileSize (mirrors config/media.php)
const COLLECTION_MAX_SIZE = {
    avatar:               '5MB',
    logo:                 '5MB',
    thumbnail:            '5MB',
    cover:                '10MB',
    jodit_content:        '10MB',
    attachments:          '50MB',
    attachments_private:  '50MB',
};

// Collection → accepted MIME types for client-side validation (mirrors config/media.php)
const COLLECTION_MIME = {
    avatar:              'image/jpeg, image/png, image/webp, image/gif',
    logo:                'image/jpeg, image/png, image/webp',
    thumbnail:           'image/jpeg, image/png, image/webp',
    cover:               'image/jpeg, image/png, image/webp',
    attachments:         null,  // any
    attachments_private: null,  // any
};

// Single-file collections — UI shows 1 file max, replaces on new upload
const SINGLE_FILE_COLLECTIONS = new Set(['avatar', 'logo', 'thumbnail', 'cover']);

const FilePondInstances = new Map();
window.FilePondInstances = FilePondInstances;

// ── initFilePond — generic, no backend integration ─────────────────────

function initFilePond(selector, options = {}) {
    const el = typeof selector === 'string'
        ? document.querySelector(selector)
        : selector;
    if (!el) { console.warn('[FilePond] Element not found:', selector); return null; }

    const pond = FilePond.create(el, { ...DEFAULTS, ...options });
    FilePondInstances.set(typeof selector === 'string' ? selector : el.id, pond);
    return pond;
}

// ── initFilePondUpload — MediaUploadService integration ────────────────

/**
 * Create a FilePond instance wired to POST /api/v1/media/upload.
 *
 * @param {string|HTMLElement} selector
 * @param {object} options
 *   collection   {string}            required — 'avatar'|'logo'|'thumbnail'|'cover'|'attachments'|'attachments_private'
 *   contextType  {string}            optional — entity_type for direct association on edit forms (e.g. 'employee')
 *   contextId    {number}            optional — entity numeric ID (edit form only)
 *   bindTo       {string|HTMLInput}  optional — hidden input element or selector to auto-populate with uuid(s)
 *                                    1-1 collections: value = single uuid string
 *                                    1-n collections: value = JSON array of uuids
 *   onUploaded   {Function}          fn(uuid, url, thumbUrl) — called after each successful upload
 *   onReverted   {Function}          fn(uuid) — called when user cancels/removes an uploaded file
 *   [+ any FilePond option]
 *
 * Returns: FilePond instance
 *
 * ── Create form (no contextId) ─────────────────────────────────────────
 *   Files go to FilePondDraft (orphan). On form submit, backend must call:
 *     MediaUploadService::reassociateFilePondDrafts($model, $uuids, $collection)
 *   UUIDs are available from bindTo input value or onUploaded callbacks.
 *
 * ── Edit form (contextId set) ──────────────────────────────────────────
 *   Files attached directly to entity. allowRevert=true so user can undo
 *   the upload they just made (DELETE authorized for own uploads).
 */
function initFilePondUpload(selector, options = {}) {
    const el = typeof selector === 'string'
        ? document.querySelector(selector)
        : selector;
    if (!el) { console.warn('[FilePondUpload] Element not found:', selector); return null; }

    const {
        collection,
        contextType,
        contextId,
        bindTo,
        onUploaded,
        onReverted,
        // Remaining keys forwarded to FilePond as-is
        ...rest
    } = options;

    if (!collection) {
        console.error('[FilePondUpload] option "collection" is required');
        return null;
    }

    const isSingle  = SINGLE_FILE_COLLECTIONS.has(collection);
    const maxSize   = COLLECTION_MAX_SIZE[collection] ?? '10MB';
    const mimeTypes = COLLECTION_MIME[collection] ?? null;
    const isEditForm = Boolean(contextType && contextId);

    // Resolve bindTo element once at init time (element must exist in DOM)
    const bindInput = bindTo
        ? (typeof bindTo === 'string' ? document.querySelector(bindTo) : bindTo)
        : null;
    // Internal uuid set for 1-n bindTo sync; irrelevant for 1-1 (single string)
    const boundUuids = new Set();

    // CSRF + collection headers read lazily at request time (not at init time)
    const buildHeaders = () => ({
        'X-CSRF-TOKEN':  document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        'Accept':        'application/json',
        'X-Collection':  collection,
        ...(contextType ? { 'X-Context-Type': String(contextType) } : {}),
        ...(contextId   ? { 'X-Context-Id':   String(contextId)   } : {}),
    });

    const pond = FilePond.create(el, {
        ...DEFAULTS,
        allowMultiple: !isSingle,
        maxFiles:      isSingle ? 1 : (rest.maxFiles ?? 10),
        maxFileSize:   maxSize,
        ...(mimeTypes ? { acceptedFileTypes: mimeTypes.split(', ') } : {}),

        server: {
            // process: upload file → returns JSON {uuid, url, thumb_url, original}
            process: {
                url:     '/api/v1/media/upload',
                method:  'POST',
                headers: buildHeaders,
                onload(response) {
                    const data = JSON.parse(response);
                    // ── bindTo: auto-populate hidden input ──────────────
                    if (bindInput) {
                        if (isSingle) {
                            bindInput.value = data.uuid;
                        } else {
                            boundUuids.add(data.uuid);
                            bindInput.value = JSON.stringify([...boundUuids]);
                        }
                    }
                    onUploaded?.(data.uuid, data.url, data.thumb_url);
                    // FilePond stores this as the server file ID (used in revert)
                    return data.uuid;
                },
                onerror(response) {
                    try {
                        return JSON.parse(response).message ?? 'Upload thất bại';
                    } catch {
                        return 'Upload thất bại';
                    }
                },
            },

            // revert: DELETE /api/v1/media/upload/{uuid}
            // Works for both draft (create form) and own direct upload (edit form).
            revert: (uuid, load, error) => {
                fetch(`/api/v1/media/upload/${uuid}`, {
                    method:  'DELETE',
                    headers: buildHeaders(),
                })
                    .then(res => {
                        if (res.ok) {
                            // ── bindTo: remove uuid from hidden input ───
                            if (bindInput) {
                                if (isSingle) {
                                    bindInput.value = '';
                                } else {
                                    boundUuids.delete(uuid);
                                    bindInput.value = JSON.stringify([...boundUuids]);
                                }
                            }
                            onReverted?.(uuid);
                            load();
                        } else {
                            res.json()
                               .then(d => error(d.message ?? 'Không thể hủy'))
                               .catch(() => error('Không thể hủy'));
                        }
                    })
                    .catch(() => error('Không thể hủy'));
            },
        },

        // Forward any remaining options (e.g. imagePreviewHeight, stylePanelLayout...)
        ...rest,
    });

    const instanceKey = typeof selector === 'string' ? selector : (el.id || collection);
    FilePondInstances.set(instanceKey, pond);
    return pond;
}

// ── Exports ────────────────────────────────────────────────────────────

window.initFilePond       = initFilePond;
window.initFilePondUpload = initFilePondUpload;
window.FilePond           = FilePond;

export { initFilePond, initFilePondUpload, FilePond, FilePondInstances };
export default FilePond;

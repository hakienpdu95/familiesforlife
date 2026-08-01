/**
 * Modules/ContentCalendar/resources/assets/js/shared/entry-dialog-select.js
 *
 * docs/form-ui-spec.md §22.6/§15 — cả 3 select (category/origin/assigned_to) + field ngày
 * (Flatpickr) trong entryDialog nằm bên trong 1 <dialog> ĐÓNG lúc trang tải (display:none tương
 * đương x-show ẩn) — init ngay lúc DOMContentLoaded (kiểu `ts-init`/`fp-init` auto-scan thường)
 * sẽ đo kích thước = 0, dropdown/calendar vỡ layout. Dùng đúng pattern §22.6: KHÔNG gắn
 * `ts-init`/`fp-init`, tự init sau khi dialog đã hiện (showModal()), bọc `requestAnimationFrame`
 * để chắc chắn Alpine đã patch xong DOM (giá trị x-model) VÀ trình duyệt đã layout dialog
 * (kích thước > 0) trước khi TomSelect/Flatpickr đo.
 *
 * TomSelect tự đọc `<option disabled>` từ select gốc khi khởi tạo (getSettings.ts:
 * `option_data[disabledField] = option.disabled`) — category ngoài phạm vi platform_section_editor
 * (đã disabled ở Alpine x-for, xem ListCategoryTreeOptionsAction) tự động bị khoá trong dropdown,
 * không cần cấu hình thêm gì ở đây.
 *
 * ── Vấn đề chung cho CẢ 2 widget: dropdown/calendar mặc định append vào <body> ──────────────
 * entryDialog là <dialog class="modal"> THẬT (native), showModal() đưa nó vào "top layer" của
 * trình duyệt — 1 lớp render tách biệt, LUÔN vẽ ĐÈ LÊN mọi nội dung <body> thường bất kể z-index.
 * Nếu dropdown/calendar bị append vào <body> (nằm NGOÀI top layer), nó bị vẽ NẰM DƯỚI dialog —
 * mở ra không thấy gì. Phải đổi điểm append sang chính <dialog>.
 *
 * NHƯNG: cả TomSelect (`positionDropdown()`) lẫn Flatpickr (`positionCalendar()`) đều tính
 * top/left dựa trên giả định điểm append là `document.body` (viewport/document-relative). Đọc
 * thẳng source: TomSelect chỉ tính vị trí khi `dropdownParent === 'body'` (so sánh chuỗi — truyền
 * HTMLElement khác sẽ khiến nó return sớm, KHÔNG set gì); Flatpickr luôn tính theo
 * `window.pageYOffset + inputBounds.top`, đúng cho <body> (position gốc tại đỉnh document) nhưng
 * SAI khi cha thật sự là <dialog> (position:fixed theo UA stylesheet mặc định của dialog:modal —
 * con position:absolute bên trong tính theo hộp của dialog, không theo document). Kết quả nếu
 * không tự sửa: dropdown/calendar bị lệch hẳn ra ngoài màn hình (đã đo được top:904px trong
 * viewport 900px lúc debug bằng Puppeteer). → Cả 2 đều cần tự tính lại vị trí bằng tay mỗi lần mở
 * (hook `dropdown_open`/`onOpen`), tính theo toạ độ TƯƠNG ĐỐI với <dialog>.
 */
import { createTs } from '@shared/tom-select-factory.js';

function _positionWithinDialog(popupEl, anchorEl, dialogEl, { offsetTop = 0, matchWidth = true } = {}) {
    const anchorRect = anchorEl.getBoundingClientRect();
    const dialogRect = dialogEl.getBoundingClientRect();

    popupEl.style.position = 'absolute';
    popupEl.style.top = `${anchorRect.bottom - dialogRect.top + offsetTop}px`;
    popupEl.style.left = `${anchorRect.left - dialogRect.left}px`;

    // Bắt buộc set width tường minh cho TomSelect dropdown — nó không còn là sibling ngay sau
    // control (đã bị move sang làm con trực tiếp của <dialog>) nên KHÔNG còn thừa hưởng width từ
    // ngữ cảnh gốc; CSS mặc định (.ts-dropdown không tự set width) để nó rộng theo container cha
    // (<dialog>) — tức rộng gần hết modal, tràn khỏi field (đúng triệu chứng đã gặp).
    //
    // Flatpickr calendar thì NGƯỢC LẠI — matchWidth=false, giữ nguyên chiều rộng tự nhiên của
    // lưới 7 cột ngày (~307px, cố định bất kể input hẹp cỡ nào) — ép width theo input sẽ bóp méo
    // lưới ngày, không phải lỗi cần sửa như TomSelect.
    if (matchWidth) {
        popupEl.style.width = `${anchorRect.width}px`;
    }
}

const SELECT_FIELDS = [
    { id: 'ts-entry-category', placeholder: '— Chọn category —' },
    { id: 'ts-entry-origin', placeholder: '— Chọn nguồn gốc —' },
    { id: 'ts-entry-assignee', placeholder: '— Chưa gán —' },
];

const DATE_FIELD_ID = 'fp-entry-target-publish-date';

function _syncSelects(dialogEl) {
    for (const { id, placeholder } of SELECT_FIELDS) {
        const el = dialogEl.querySelector(`#${id}`);
        if (!el) continue;

        if (el.tomselect) {
            // TomSelect không tự đọc lại `<select>.value` khi Alpine set trực tiếp qua x-model
            // (bỏ qua API của TomSelect) — lần mở thứ 2 trở đi phải chủ động đồng bộ bằng
            // `setValue(el.value, true)` (silent — không bắn lại 'change' để tránh vòng lặp
            // ngược vào Alpine).
            el.tomselect.setValue(el.value, true);
            continue;
        }

        const ts = createTs(el, { placeholder, dropdownParent: dialogEl });
        ts?.on('dropdown_open', () => _positionWithinDialog(ts.dropdown, ts.control, dialogEl, { matchWidth: true }));
    }
}

function _syncDatePicker(dialogEl) {
    const el = dialogEl.querySelector(`#${DATE_FIELD_ID}`);
    if (!el) return;

    if (el._flatpickr) {
        // Cùng lý do với TomSelect ở trên — silent (không trigger onChange thứ 2) tránh vòng lặp.
        el._flatpickr.setDate(el.value || null, false);
        return;
    }

    // window.initDatePicker (resources/js/modules/flatpickr.js) đã có sẵn locale tiếng Việt +
    // format 'd/m/Y' mặc định — override thêm phần riêng cho field này (lưu Y-m-d, hiện d/m/Y
    // qua altInput, và vị trí calendar tính theo <dialog> thay vì <body>, xem docblock đầu file).
    const fp = window.initDatePicker?.(el, {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd/m/Y',
        allowInput: false,
        disableMobile: true,
        appendTo: dialogEl,
        onOpen: [(_selectedDates, _dateStr, instance) => {
            _positionWithinDialog(instance.calendarContainer, instance.altInput || instance.input, dialogEl, { offsetTop: 2, matchWidth: false });
        }],
    });

    if (fp && el.value) fp.setDate(el.value, false);
}

/**
 * Gọi mỗi lần dialog mở (create lẫn edit) — KHÔNG chỉ lần đầu (xem lý do đồng bộ lại giá trị ở
 * `_syncSelects`/`_syncDatePicker`).
 *
 * @param {HTMLElement} dialogEl - `$refs.entryDialog`
 */
export function syncEntryDialogWidgets(dialogEl) {
    if (!dialogEl) return;

    requestAnimationFrame(() => {
        _syncSelects(dialogEl);
        _syncDatePicker(dialogEl);
    });
}

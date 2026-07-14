/**
 * pages/submit-form.js — form nộp sự kiện công khai (cổng thông tin).
 *
 * KHÔNG gọi initFormValidation (global chỉ có ở bundle backend/admin, resources/js/app.js) —
 * portal cố tình không load bundle đó (spec: giữ bundle portal tối giản). Validate phía client
 * chỉ dựa vào required/type HTML5 chuẩn; validate đầy đủ luôn ở server
 * (EventSubmissionController::validated()).
 */
import { initAllTomSelects } from '@shared/tom-select-factory.js';

const FORM_SEL = '[data-event-submit-form]';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initAllTomSelects(form);
    window.initAllDatePickers?.(form);
});

/**
 * pages/assessment-form.js
 *
 * Responsibilities:
 *   1. Inline validation — delegate to global initFormValidation
 *   2. TomSelect init    — organization_id (ts-init selects via initAllTomSelects)
 *   3. assessmentForm Alpine component:
 *      - live preview assessment_code (create)
 *      - reactive contextual descriptions cho aggregation_model và classification_type
 */

import { initAllTomSelects } from '@shared/tom-select-factory.js';

// ── Constants ──────────────────────────────────────────────────────────────

const FORM_SEL = '[data-assessment-form]';

const AGGREGATION_DESCS = Object.freeze({
    weighted_domain:
        'Mỗi domain (lĩnh vực) có trọng số riêng biệt. Phù hợp khi các lĩnh vực không ngang nhau về tầm quan trọng — ví dụ "Hạ tầng kỹ thuật" nặng hơn "Nhận thức ban đầu".',
    flat_sum:
        'Cộng toàn bộ điểm từ mọi câu hỏi, mỗi câu có giá trị bằng nhau. Phù hợp với khảo sát ngắn hoặc không cần phân nhóm domain.',
    sectioned:
        'Tính điểm độc lập cho từng section trong khảo sát. Phù hợp khi cần báo cáo riêng biệt theo từng phần, không gộp chung thành một điểm tổng.',
});

const CLASSIFICATION_DESCS = Object.freeze({
    score_band:
        'Xếp loại theo dải điểm đã định nghĩa sẵn (VD: 0–40 = Thấp, 41–70 = Trung bình, 71–100 = Cao). Lựa chọn phổ biến và dễ hiểu nhất với người dùng.',
    pass_fail:
        'Kết quả nhị phân: đạt nếu điểm ≥ ngưỡng tối thiểu, không đạt nếu dưới ngưỡng. Phù hợp cho đánh giá năng lực có yêu cầu đầu vào cố định.',
    persona_match:
        'Gắn kết quả với một "persona" (hồ sơ hành vi) đã định nghĩa qua tập điều kiện. Phù hợp để phân loại kiểu người dùng, không dựa vào thang điểm tuyến tính.',
    none:
        'Chỉ tính điểm số thô, không xếp loại hay phân nhóm. Dùng khi cần điểm để xử lý ở tầng khác (webhook, workflow) hoặc chỉ để thống kê.',
});

// ── Entry point ────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;
    initFormValidation(FORM_SEL);
    initAllTomSelects(form);
});

// ── Alpine component ────────────────────────────────────────────────────────

document.addEventListener('alpine:init', () => {
    Alpine.data('assessmentForm', (sd) => ({
        name:      sd.name      || '',
        aggModel:  sd.aggModel  || 'weighted_domain',
        classType: sd.classType || 'score_band',

        get preview() {
            const s = _slugify(this.name);
            return s ? s + '-xxxxxxxx' : 'tự động tạo từ tên...';
        },

        get aggDesc()   { return AGGREGATION_DESCS[this.aggModel]    ?? ''; },
        get classDesc() { return CLASSIFICATION_DESCS[this.classType] ?? ''; },
    }));
});

// ── Helpers ─────────────────────────────────────────────────────────────────

function _slugify(text) {
    return text.toLowerCase()
        .normalize('NFD').replace(/[̀-ͯ]/g, '')
        .replace(/đ/g, 'd')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');
}

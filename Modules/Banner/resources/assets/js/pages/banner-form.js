/**
 * pages/banner-form.js
 * spec/Banner_Management_Technical_Specification.md §7.4 — form admin banner:
 *   1. Gợi ý kích thước động theo placement.
 *   2. Ẩn/hiện + validate-phía-UI select category theo target_type.
 * Đăng ký qua alpine:init (cùng pattern resources/js/app.js) — chạy TRƯỚC Alpine.start()
 * nên script inline (x-data="bannerForm(...)") trong _form.blade.php dùng được ngay.
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('bannerForm', (recommendedSizes, initialPlacement, initialTargetType, initialTargetValue) => ({
        placement: initialPlacement,
        targetType: initialTargetType,
        targetValue: initialTargetValue,

        get recommendedSize() {
            return recommendedSizes[this.placement] ?? '—';
        },
        get needsCategory() {
            return this.targetType === 'category';
        },
        get isValid() {
            return ! this.needsCategory || this.targetValue !== '';
        },
    }));
});

/**
 * pages/kc-category-form.js
 * Alpine component cho create/edit form danh mục KC.
 */

// ── Vietnamese slug helper ────────────────────────────────────────────────────

function kcCatToSlug(str) {
    var map = {
        'à':'a','á':'a','ả':'a','ã':'a','ạ':'a',
        'ă':'a','ắ':'a','ặ':'a','ằ':'a','ẳ':'a','ẵ':'a',
        'â':'a','ấ':'a','ậ':'a','ầ':'a','ẩ':'a','ẫ':'a',
        'è':'e','é':'e','ẻ':'e','ẽ':'e','ẹ':'e',
        'ê':'e','ế':'e','ệ':'e','ề':'e','ể':'e','ễ':'e',
        'ì':'i','í':'i','ỉ':'i','ĩ':'i','ị':'i',
        'ò':'o','ó':'o','ỏ':'o','õ':'o','ọ':'o',
        'ô':'o','ố':'o','ộ':'o','ồ':'o','ổ':'o','ỗ':'o',
        'ơ':'o','ớ':'o','ợ':'o','ờ':'o','ở':'o','ỡ':'o',
        'ù':'u','ú':'u','ủ':'u','ũ':'u','ụ':'u',
        'ư':'u','ứ':'u','ự':'u','ừ':'u','ử':'u','ữ':'u',
        'ỳ':'y','ý':'y','ỷ':'y','ỹ':'y','ỵ':'y',
        'đ':'d',
    };
    return str.toLowerCase()
        .split('').map(function(c) { return map[c] || c; }).join('')
        .replace(/[^a-z0-9\s-]/g, '')
        .trim()
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-');
}

// ── Alpine component ──────────────────────────────────────────────────────────

document.addEventListener('alpine:init', function () {
    Alpine.data('kcCategoryForm', function (initColor) {
        return {
            slug:     '',
            colorHex: initColor || '#6366f1',
            slugEdited: false,

            init: function () {
                // Nếu input[name=slug] đã có giá trị (edit form), đánh dấu đã chỉnh
                var slugInput = this.$el.querySelector('[name="slug"]');
                if (slugInput && slugInput.value) {
                    this.slug = slugInput.value;
                    this.slugEdited = true;
                }
            },

            onNameInput: function (val) {
                if (!this.slugEdited) {
                    this.slug = kcCatToSlug(val);
                }
            },
        };
    });
});

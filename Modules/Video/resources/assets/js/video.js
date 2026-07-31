/**
 * Modules/Video/resources/assets/js/video.js
 * Entry point JS module Video.
 * Build: vite.config.backend.js → public/build/backend/assets/modules/video.[hash].js
 */
import './pages/video-index.js';

/**
 * Nâng cấp ảnh đại diện video lên Full HD (maxresdefault.jpg) khi có thật — dùng chung cho
 * Tabulator cột "Ảnh" + preview form sửa. Trang công khai (public/index.blade.php) không load
 * bundle này (không cần Tabulator) nên có 1 bản inline tương đương, xem file đó.
 *
 * Cách hoạt động: `<img src>` LUÔN bắt đầu bằng bản an toàn (hqdefault.jpg, thumbnail_url —
 * đảm bảo tồn tại) — HTML gốc không phụ thuộc JS để hiển thị đúng. Hàm này "dò" ảnh HD
 * (data-thumb-hd = thumbnail_hd_url) trong 1 `Image()` tách biệt KHÔNG gắn vào DOM, chỉ đổi
 * `src` của ảnh thật sang bản HD nếu dò thành công VÀ xác nhận không phải placeholder.
 *
 * LƯU Ý bắt buộc dùng cách "dò" này (không dùng onload/onerror trực tiếp trên ảnh hiển thị):
 * video thiếu bản HD KHÔNG trả lỗi 404 thật — YouTube trả về ảnh xám placeholder cỡ ĐÚNG
 * 120×90 kèm HTTP 200 — nên phải kiểm tra naturalWidth mới phân biệt được. Dò trên ảnh tách
 * biệt (không phải ảnh đang hiển thị) còn tránh được race condition nếu gắn onload trực tiếp
 * lên ảnh hiển thị trong HTML server-render — trình duyệt có thể bắt đầu tải + bắn sự kiện
 * `load` của ảnh đó TRƯỚC KHI script module (nạp cuối trang qua @push('scripts')) kịp chạy và
 * định nghĩa hàm xử lý, dẫn tới lỗi "hàm chưa tồn tại" ngẫu nhiên tuỳ tốc độ mạng/cache.
 */
function upgradeVideoThumbnails(root) {
    (root || document).querySelectorAll('img[data-thumb-hd]:not([data-thumb-upgraded])').forEach((img) => {
        img.dataset.thumbUpgraded = '1';

        const hdUrl = img.dataset.thumbHd;
        const probe = new Image();
        probe.onload = () => {
            if (probe.naturalWidth > 120) img.src = hdUrl;
        };
        probe.src = hdUrl;
    });
}

window.videoUpgradeThumbnails = upgradeVideoThumbnails;
document.addEventListener('DOMContentLoaded', () => upgradeVideoThumbnails());

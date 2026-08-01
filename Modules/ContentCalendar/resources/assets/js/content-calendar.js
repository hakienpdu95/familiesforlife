/**
 * Modules/ContentCalendar/resources/assets/js/content-calendar.js
 * Entry point JS module ContentCalendar — cùng convention Modules/Aicem/resources/assets/js/aicem.js.
 * Build: vite.config.backend.js → public/build/backend/assets/modules/content-calendar.[hash].js
 *
 * Blade:
 *   @push('scripts')
 *       @vite(['Modules/ContentCalendar/resources/assets/js/content-calendar.js'], 'build/backend')
 *   @endpush
 *
 * Cả 2 trang (board, calendar) load CHUNG entry này — mỗi file trong pages/ chỉ đăng ký
 * Alpine.data() của riêng nó (qua alpine:init), không tự chạy code nào tại import-time, nên load
 * thừa 1 component không dùng tới trên trang kia là vô hại (không có side-effect ngoài ý muốn).
 */
import './pages/board.js';
import './pages/calendar.js';

/**
 * resources/js/anland.js
 * ────────────────────────────────────────────────────────────────────
 * Entry point riêng cho portal BĐS Anland (/anland). Tách khỏi
 * frontend.js (trang chủ familiesforlife) — chỉ chứa state Alpine cho
 * header/search/calculator của riêng portal này.
 */
import Alpine from 'alpinejs';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    /** Header Anland — chỉ quản lý mở/đóng menu mobile (nav item render server-side). */
    Alpine.data('anlandNav', () => ({
        mobileNavOpen: false,
    }));

    /**
     * anlandSearch — hero search widget: tab Mua bán/Cho thuê đổi action của <form> sang
     * đúng route index tương ứng (route('real-estate.public.sale.index') / rent.index truyền
     * vào từ Blade, KHÔNG hardcode URL trong JS) trước khi submit GET (query string chuẩn,
     * hỗ trợ back/forward + chia sẻ link kết quả lọc).
     */
    Alpine.data('anlandSearch', (initialTab, saleUrl, rentUrl) => ({
        tab: initialTab,
        saleUrl,
        rentUrl,

        get formAction() {
            return this.tab === 'rent' ? this.rentUrl : this.saleUrl;
        },
    }));

    /**
     * anlandLoanCalculator — công cụ ước tính khoản vay mua nhà, tính hoàn toàn phía client
     * (không gửi dữ liệu tài chính người dùng lên server). Công thức trả góp đều gốc+lãi
     * (annuity): M = P * r(1+r)^n / ((1+r)^n - 1), r = lãi suất năm/12, n = số kỳ hạn (tháng).
     */
    Alpine.data('anlandLoanCalculator', () => ({
        price: 3000000000,
        downPaymentPercent: 30,
        annualRatePercent: 9.5,
        years: 20,

        get loanAmount() {
            return Math.max(0, this.price * (1 - this.downPaymentPercent / 100));
        },

        get monthlyPayment() {
            const principal = this.loanAmount;
            const months = this.years * 12;
            const monthlyRate = this.annualRatePercent / 100 / 12;

            if (principal <= 0 || months <= 0) return 0;
            if (monthlyRate === 0) return principal / months;

            const factor = Math.pow(1 + monthlyRate, months);

            return principal * monthlyRate * factor / (factor - 1);
        },

        get totalPayment() {
            return this.monthlyPayment * this.years * 12;
        },

        get totalInterest() {
            return Math.max(0, this.totalPayment - this.loanAmount);
        },

        formatVnd(value) {
            return Math.round(value).toLocaleString('vi-VN') + ' đ';
        },
    }));
});

Alpine.start();

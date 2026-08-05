<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bài toán #27 (spec/giadinh.md — Quyết định 1193/QĐ-UBND, "hệ thống phân tích và dự báo
     * nhu cầu an sinh xã hội") — thống kê TỔNG HỢP, ẨN DANH, CHỈ GỬI KHI NGƯỜI DÙNG TỰ NGUYỆN BẤM
     * nút "Đóng góp dữ liệu ẩn danh" (opt-in, mặc định KHÔNG gửi gì — xem
     * PublicEstimation/Http/PensionCalculatorController::logUsage()).
     *
     * CỐ Ý KHÔNG có: thu nhập, số tiền đóng/hưởng, năm sinh, ngày tháng cụ thể, IP, session/
     * cookie, hay bất kỳ trường nào có thể suy ngược ra danh tính hay tài chính cá nhân — chỉ
     * lưu các mốc THÔ đủ để nhìn xu hướng tổng thể (bao nhiêu người chưa đủ điều kiện, thiếu
     * trung bình bao nhiêu năm, có dùng nhóm hỗ trợ hay không...). Giữ đúng cam kết "không lưu
     * trữ thông tin cá nhân" đã nêu trong disclaimer của trang — chỉ mở rộng thêm 1 lựa chọn TỰ
     * NGUYỆN đóng góp dữ liệu hoàn toàn ẩn danh, không phải thu thập ngầm.
     */
    public function up(): void
    {
        Schema::create('pension_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('gender', ['male', 'female']);
            $table->boolean('has_mandatory_history')->default(false);
            $table->boolean('uses_support_group')->default(false);
            $table->string('eligibility_branch', 1); // 'a'|'b'|'c'|'d' — xem pensionEligibility() JS
            $table->boolean('eligible_by_years')->default(false);
            $table->unsignedSmallInteger('years_accumulated'); // làm tròn xuống, KHÔNG phải số tháng chính xác
            $table->unsignedSmallInteger('years_required');
            $table->timestamp('created_at')->useCurrent(); // không có updated_at — bản ghi bất biến, ghi 1 lần
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pension_usage_logs');
    }
};

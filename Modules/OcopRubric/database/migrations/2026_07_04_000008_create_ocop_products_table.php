<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ocop_products', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_group_id')->constrained('ocop_product_groups')->restrictOnDelete();
            $table->string('name', 255);
            $table->string('product_code', 60)->nullable();            // (MãTỉnh)-(MãXã)-(STT)-(Năm) — tự điền khi có
            $table->string('status', 20)->default('draft');            // ProductStatus

            // Tách riêng "kỷ lục luyện tập" và "hiện trạng tự đánh giá" — KHÔNG dùng chung 1 cặp
            // best_score/best_star_rank (xem spec §18 Key Design Decisions — lý do tách).
            $table->decimal('best_practice_score', 5, 2)->nullable();
            $table->unsignedTinyInteger('best_practice_star_rank')->nullable();
            $table->decimal('latest_self_assessment_score', 5, 2)->nullable();
            $table->unsignedTinyInteger('latest_self_assessment_star_rank')->nullable();
            $table->unsignedBigInteger('latest_self_assessment_session_id')->nullable();
            // FK cho cột trên thêm ở migration riêng (2026_07_04_000012) SAU KHI ocop_scoring_sessions
            // tồn tại — 2 bảng tham chiếu vòng lẫn nhau (products → sessions và sessions → products),
            // không thể đặt cả 2 FK ràng buộc ngay trong migration tạo bảng đầu tiên.

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'product_group_id']);
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ocop_products');
    }
};

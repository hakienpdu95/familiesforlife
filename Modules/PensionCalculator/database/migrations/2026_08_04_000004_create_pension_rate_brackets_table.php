<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // spec §6.9/§8.2/§14 mục 1 — bảng tỷ lệ hưởng lương hưu hằng tháng theo (gender, số năm
        // đóng). CHƯA xác minh với Luật BHXH 2024 tại thời điểm v1.1 — bảng để TRỐNG cho tới khi
        // đối chiếu Điều 66/99, KHÔNG seed số liệu suy đoán (§0 hàng "Tỷ lệ hưởng lương hưu").
        Schema::create('pension_rate_brackets', function (Blueprint $table) {
            $table->id();
            $table->string('gender');
            $table->unsignedTinyInteger('min_years_for_base_rate');
            $table->decimal('base_rate_percent', 5, 2);
            $table->decimal('increment_percent_per_year', 5, 2);
            $table->decimal('max_rate_percent', 5, 2)->default(75.00);
            $table->date('effective_from');
            $table->string('source_document');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pension_rate_brackets');
    }
};

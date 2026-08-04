<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // spec §6.6/§8.2 — hệ số trượt giá do BHXH Việt Nam công bố hàng năm, tra theo cặp
        // (settlement_year = năm hưởng, contribution_year = năm đã đóng). Nhập lại nguyên văn
        // bảng đã công bố — module KHÔNG tự tính CPI.
        Schema::create('pension_price_index_coefficients', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('settlement_year');
            $table->unsignedSmallInteger('contribution_year');
            $table->decimal('coefficient', 4, 2);
            $table->string('source_document');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['settlement_year', 'contribution_year'], 'pension_price_index_coef_year_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pension_price_index_coefficients');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ocop_product_groups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 60)->unique();                    // 'rau-cu-qua-hat-tuoi'
            $table->string('name', 255);                             // "Rau, củ, quả, hạt tươi"
            $table->string('industry_code', 10);                     // 'I'..'VI' — Phụ lục I
            $table->string('industry_name', 255);                    // "SẢN PHẨM THỰC PHẨM"
            $table->string('group_label', 255)->nullable();          // "Nhóm: Thực phẩm tươi sống"
            $table->string('managing_agency', 255)->nullable();      // "Bộ Nông nghiệp và Môi trường"
            $table->boolean('requires_sample_product')->default(true); // false cho bộ #26 (Điều 6.2.d)
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['industry_code', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ocop_product_groups');
    }
};

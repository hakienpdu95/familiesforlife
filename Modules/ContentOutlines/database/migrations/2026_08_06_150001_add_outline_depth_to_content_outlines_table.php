<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// spec/ContentOutlines_Technical_Specification.md §4.1 (v1.1) — kiểm soát độ dài prompt: 'brief'
// cắt ngắn ngữ cảnh chuyên mục + rút gọn 9 bước, 'detailed' bỏ giới hạn + thêm chỉ dẫn sâu hơn.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_outlines', function (Blueprint $table) {
            $table->string('outline_depth', 10)->default('standard')->after('language');
        });
    }

    public function down(): void
    {
        Schema::table('content_outlines', function (Blueprint $table) {
            $table->dropColumn('outline_depth');
        });
    }
};

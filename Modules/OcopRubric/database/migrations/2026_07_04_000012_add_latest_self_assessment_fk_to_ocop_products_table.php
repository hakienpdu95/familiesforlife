<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tách riêng vì phụ thuộc vòng: ocop_products cần trỏ tới ocop_scoring_sessions (bảng tạo SAU
// nó ở 000009) — không thể gộp FK này vào migration 000008 tạo bảng ocop_products.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ocop_products', function (Blueprint $table) {
            $table->foreign('latest_self_assessment_session_id')
                ->references('id')->on('ocop_scoring_sessions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ocop_products', function (Blueprint $table) {
            $table->dropForeign(['latest_self_assessment_session_id']);
        });
    }
};

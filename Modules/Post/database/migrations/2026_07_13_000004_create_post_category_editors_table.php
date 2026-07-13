<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/Platform_RBAC_Phase2_Specification.md §4.2 (v3.0) — gán platform_section_editor phụ
 * trách 1 hoặc vài chuyên mục cụ thể. user_id luôn là tài khoản organization_id=null
 * (platform_section_editor), không theo tổ chức nào — không cần cột organization_id ở bảng
 * này (khác bản v1.2 từng có, đã bỏ).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('post_category_editors')) {
            return;
        }

        Schema::create('post_category_editors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['post_category_id', 'user_id'], 'uq_post_category_editor');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_category_editors');
    }
};

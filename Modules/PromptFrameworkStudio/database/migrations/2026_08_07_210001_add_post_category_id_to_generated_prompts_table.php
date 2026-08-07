<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// spec/PromptFrameworkStudio_Technical_Specification.md §3.1 (v2.7) — chuyên mục người dùng chọn
// khi sinh prompt, dùng để tra `CategoryContentFoundation` và chèn khối "Bối cảnh biên tập".
// NULLABLE + nullOnDelete: chuyên mục là TUỲ CHỌN (công cụ này soạn được cả prompt không liên quan
// nội dung gia đình — dịch thuật, sửa code...), và xoá chuyên mục KHÔNG được kéo theo prompt đã
// sinh (rendered_prompt đã lưu vẫn còn nguyên giá trị sử dụng, cùng tinh thần graceful degrade với
// framework orphaned ở §5.4).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generated_prompts', function (Blueprint $table) {
            $table->foreignId('post_category_id')
                ->nullable()
                ->after('framework_key')
                ->constrained('post_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('generated_prompts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('post_category_id');
        });
    }
};

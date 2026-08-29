<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// spec/ContentOutlines_Technical_Specification.md §4.28 (v1.26) — audience-first content brief:
// 2 field MỚI để neo prompt vào NHIỆM VỤ/CẢM XÚC thật của độc giả (job_to_be_done/
// reader_emotional_state), khác `target_audience` (mô tả TĨNH ai là độc giả) — dùng trong bước
// "Xác nhận ý định tìm kiếm" (Bản đồ Thấu cảm) + neo USP/CTA theo JTBD ở BuildContentOutlinePromptAction.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_outlines', function (Blueprint $table) {
            $table->string('job_to_be_done', 300)->nullable()->after('target_audience');
            $table->string('reader_emotional_state', 300)->nullable()->after('job_to_be_done');
        });
    }

    public function down(): void
    {
        Schema::table('content_outlines', function (Blueprint $table) {
            $table->dropColumn(['job_to_be_done', 'reader_emotional_state']);
        });
    }
};

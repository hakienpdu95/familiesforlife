<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// (2026-08-28, phản hồi review spec/TopicClusterGenerator.md) — kết quả AI (paste lại) của 1
// GeneratedPrompt dùng framework `topiccluster`, đã phân tích thành Pillar/Cluster để duyệt theo
// từng mục (checkbox) rồi đẩy sang Modules\ContentOutlines. KHÔNG gộp vào bảng `generated_prompts`
// (dùng chung bởi 27+ framework khác không có khái niệm Pillar/Cluster) — tách bảng riêng, 1-1 với
// `generated_prompts` qua `generated_prompt_id` (unique).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topic_cluster_results', function (Blueprint $table) {
            $table->id();

            $table->foreignId('generated_prompt_id')->unique()->constrained('generated_prompts')->cascadeOnDelete();

            $table->longText('ai_result_raw'); // nguyên văn Markdown AI trả về, người dùng dán vào

            // {pillar: {title, target_keyword, content_outline_uuid: string|null}, clusters: [{title,
            // target_keyword, content_outline_uuid: string|null}, ...]} — xem
            // ParseTopicClusterAiResultAction. content_outline_uuid null = CHƯA đẩy sang
            // ContentOutlines; khác null = đã đẩy, giữ nguyên uuid để hiện link + chặn đẩy trùng.
            $table->json('structured');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topic_cluster_results');
    }
};

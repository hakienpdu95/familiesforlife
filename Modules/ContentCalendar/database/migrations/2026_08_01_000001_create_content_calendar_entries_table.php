<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/ContentCalendar_Technical_Specification.md §5.1 — hàng đợi ý tưởng đã chọn + lịch xuất
 * bản, nối giữa CoreIdeaExtractor (sinh ý tưởng, không lưu) và Post (bài viết thật). Platform-wide
 * (không có organization_id) — cùng nguyên tắc post_categories/post_articles/cie_category_foundations
 * (spec §4, Lớp A).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_calendar_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // 1 entry luôn thuộc đúng 1 category — cùng đơn vị scope với post_category_editors,
            // để platform_section_editor lọc "kế hoạch của category mình" không cần bảng nối riêng.
            $table->foreignId('post_category_id')->constrained('post_categories')->cascadeOnDelete();

            $table->string('title', 255);
            $table->text('brief')->nullable(); // góc nhìn/tóm tắt ngắn — KHÔNG phải nội dung bài

            // Nguồn gốc ý tưởng — thuần audit/hiển thị, không dùng để rẽ nhánh logic (§5.4).
            $table->string('origin', 30)->default('manual'); // manual | core_idea_extractor | aicem
            $table->text('origin_note')->nullable(); // vd dán tay dòng ý tưởng+lý do từ bảng Layer 2

            $table->string('status', 20)->default('idea'); // xem CalendarEntryStatus (§5.3)
            $table->date('target_publish_date')->nullable();

            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            // Set khi bắt đầu viết thật — từ lúc này trạng thái HIỂN THỊ ưu tiên đọc từ
            // postArticle->mainTranslation->status (§5.2/§5.3.1), không đọc cột `status` ở trên nữa.
            $table->foreignId('post_article_id')->nullable()->unique()->constrained('post_articles')->nullOnDelete();

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['post_category_id', 'target_publish_date'], 'cc_entries_category_date_idx');
            $table->index('status', 'cc_entries_status_idx');
            // 2 index dưới phục vụ đúng 2 lát cắt Policy::view() lọc theo ownership (§6.3, §7.1) —
            // "entry của tôi, chưa xong" và "entry tôi được gán, chưa xong" đều WHERE kèm status.
            $table->index(['assigned_to', 'status'], 'cc_entries_assignee_status_idx');
            $table->index(['created_by', 'status'], 'cc_entries_creator_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_calendar_entries');
    }
};

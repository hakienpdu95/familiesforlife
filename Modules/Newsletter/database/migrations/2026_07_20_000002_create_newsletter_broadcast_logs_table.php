<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/Newsletter_Technical_Specification.md §4.2/§0 mục 13 — audit log append-only cho mỗi
 * lần gửi Broadcast. Không FK sang newsletter_subscribers (Broadcast gửi tới cả Segment, không
 * gắn với 1 subscriber cụ thể nào). Không có updated_at — đúng tiền lệ post_publishing_logs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_broadcast_logs', function (Blueprint $table) {
            $table->id();
            $table->string('resend_broadcast_id', 36)->nullable(); // null nếu request tạo broadcast thất bại trước khi có id
            $table->string('subject', 255);
            $table->timestamp('scheduled_at')->nullable(); // null = gửi ngay lúc đó, không lên lịch
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index('sent_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_broadcast_logs');
    }
};

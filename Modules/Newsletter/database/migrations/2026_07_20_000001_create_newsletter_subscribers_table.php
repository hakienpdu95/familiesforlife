<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/Newsletter_Technical_Specification.md §4.1 — platform-wide, không organization_id
 * (§0 mục 1). Cột confirmed_at/status pending_confirmation thêm ngay từ đầu dù double opt-in
 * tắt mặc định (§0 mục 14) — tránh backfill migration sau.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique(); // khoá công khai nội bộ (route-model-binding admin), §0 mục 5
            $table->string('full_name', 150);
            $table->string('email', 255)->unique();
            $table->string('resend_contact_id', 36)->nullable(); // id contact bên Resend, null nếu đồng bộ thất bại/đang chờ xác nhận
            $table->string('status', 20)->default('active'); // SubscriberStatus: pending_confirmation|active|unsubscribed|bounced|complained
            $table->string('source', 50)->nullable(); // vd 'public_form'
            $table->timestamp('subscribed_at')->useCurrent();
            $table->timestamp('confirmed_at')->nullable(); // §0 mục 14 — chỉ có giá trị khi double opt-in đang/đã bật
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('resend_contact_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
    }
};

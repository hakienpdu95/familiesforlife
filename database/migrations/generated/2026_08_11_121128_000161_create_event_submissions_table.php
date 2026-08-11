<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('event_submissions')) {
            return;
        }

        Schema::create('event_submissions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete()->unique()->comment('Quan hệ 1:1 với events');
            $table->string('submitter_first_name', 100)->comment('Tên người nộp');
            $table->string('submitter_last_name', 100)->comment('Họ người nộp');
            $table->string('submitter_email', 255)->comment('Email người nộp — KHÔNG hiển thị công khai');
            $table->boolean('newsletter_consent')->default(false)->comment('Đồng ý nhận bản tin');
            $table->timestamp('consented_at')->nullable()->comment('Thời gian đồng ý');
            $table->string('source', 20)->default('public_form')->comment('public_form|admin');
            $table->string('ip_address', 45)->nullable()->comment('Dấu vết chống spam');
            $table->text('user_agent')->nullable()->comment('Dấu vết chống spam');
            $table->boolean('turnstile_verified')->default(false)->comment('Đã xác thực Turnstile');
            $table->timestamps();

            // Indexes
            $table->index('submitter_email');
            $table->index('ip_address');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('event_submissions');
    }
};

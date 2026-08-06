<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// spec/N8n_Integration_Technical_Specification.md §2.4 — vì module không còn dựa vào lịch sử
// chạy của module khác. Bảng audit — không phải thực thể nghiệp vụ (cùng lý do §2.3).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('n8n_outbound_logs', function (Blueprint $table) {
            $table->id();
            // KHÔNG có organization_id — xem §0.
            $table->foreignId('connection_id')->constrained('n8n_connections')->cascadeOnDelete();
            $table->string('event_name', 100)->nullable();
            $table->string('caller', 150)->nullable();
            $table->boolean('success');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('error_message', 500)->nullable();
            $table->text('payload_excerpt')->nullable(); // bên gọi TỰ chủ động gửi, luôn ghi được.
            $table->timestamp('requested_at');

            $table->index(['connection_id', 'requested_at']);
            $table->index('requested_at');
            $table->index(['success', 'requested_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('n8n_outbound_logs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// spec/N8n_Integration_Technical_Specification.md §2.3 — audit MỌI lệnh gọi vào, kể cả không
// khớp gì. Bảng audit tần suất cao — KHÔNG soft-delete, KHÔNG activity log, KHÔNG organization_id.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('n8n_inbound_logs', function (Blueprint $table) {
            $table->id();
            // KHÔNG có organization_id — xem §0. Payload có thể NHẮC tới 1 tổ chức cụ thể (VD
            // lead_id), nhưng đó là dữ liệu nghiệp vụ nằm trong payload_excerpt, không phải cột
            // scope của bảng này.
            $table->foreignId('connection_id')->nullable()->constrained('n8n_connections')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->boolean('signature_valid')->nullable(); // null = connection không cấu hình secret.
            $table->unsignedSmallInteger('http_status_returned');
            $table->string('event_name', 100)->nullable();
            $table->unsignedTinyInteger('listener_count')->default(0);
            $table->text('payload_excerpt')->nullable(); // CHỈ ghi khi xác thực chữ ký thành công (§5.5).
            $table->string('error_message', 500)->nullable();
            $table->timestamp('received_at');

            $table->index(['connection_id', 'received_at']);
            $table->index('received_at');
            $table->index('event_name');
            $table->index('signature_valid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('n8n_inbound_logs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// spec/N8n_Integration_Technical_Specification.md §2.1 — đơn vị cấu hình trung tâm. Thuộc HỆ
// THỐNG, KHÔNG có organization_id (§0/§2.1) — khác mọi bảng nghiệp vụ khác trong Modules.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('n8n_connections', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // định danh public — sinh qua static::creating(), cùng
            // quy ước PostCategory (Modules/Post/app/Models/PostCategory.php) — KHÔNG dùng HasUuids.

            // KHÔNG có organization_id — xem §0.
            $table->string('name')->unique(); // §2.1: unique tính CẢ hàng soft-deleted (MySQL/SQLite
            // default), tên của 1 kết nối đã xoá mềm KHÔNG được tái sử dụng — chủ ý (§2.5/§7.1).
            $table->string('purpose_note', 500)->nullable();
            $table->boolean('inbound_enabled')->default(false);
            $table->boolean('outbound_enabled')->default(false);
            $table->string('inbound_token', 64)->unique(); // KHÔNG nullable — sinh ngay lúc tạo (§3.2).
            $table->text('inbound_secret')->nullable();       // cast encrypted (§2.1).
            $table->text('outbound_webhook_url')->nullable(); // cast encrypted (§2.1).
            $table->text('outbound_secret')->nullable();      // cast encrypted; NULL = gửi không ký (§4.1).
            $table->json('allowed_ip_cidrs')->nullable();
            $table->unsignedSmallInteger('rate_limit_per_minute')->nullable();
            $table->timestamp('last_inbound_at')->nullable();
            $table->timestamp('last_outbound_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('n8n_connections');
    }
};

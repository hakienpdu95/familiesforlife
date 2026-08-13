<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/Entity_Comparison_Module_Technical_Spec.md §3.2 — đối tượng cụ thể (Trường A, Trường B...).
 * Ảnh đại diện qua Media (collection `cover`, HasTenantMedia) — KHÔNG có cột `image` phẳng
 * (§0 mục 3). `restrictOnDelete()` trên entity_type_id không chặn soft-delete EntityType (soft
 * delete không xóa hàng thật) — rule "không tạo Entity mới thuộc type đã xóa mềm" enforce ở
 * FormRequest + Action (§9), không phải DB constraint.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entities', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('entity_type_id')->constrained('entity_types')->restrictOnDelete();
            $table->string('name', 150);
            $table->string('slug', 180)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['entity_type_id', 'is_active'], 'idx_entities_type_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entities');
    }
};

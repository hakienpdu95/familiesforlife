<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/Page_Static_Pages_Technical_Specification.md §2.2 — trang tĩnh là tài sản nền tảng,
 * không organization_id (cùng nguyên tắc MenuItem/Banner), không bảng dịch riêng ở v1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('slug', 160)->unique();
            $table->string('title', 200);
            $table->string('template', 60)->default('default'); // xem PageTemplate registry
            $table->longText('content')->nullable();      // rỗng khi status=draft hoặc template != 'default'
            $table->string('excerpt', 500)->nullable();

            $table->string('status', 20)->default('draft'); // PageStatus enum
            $table->timestamp('published_at')->nullable();

            $table->boolean('is_system')->default(false);   // trang seed sẵn — không thể xoá (§3.3)

            $table->string('seo_title', 200)->nullable();
            $table->string('seo_description', 300)->nullable();
            $table->boolean('seo_noindex')->default(false);

            $table->unsignedBigInteger('view_count')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at'], 'idx_page_status_published');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};

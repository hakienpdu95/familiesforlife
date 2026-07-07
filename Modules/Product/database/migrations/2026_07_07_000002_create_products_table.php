<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('name', 250);
            $table->string('slug', 270);
            $table->string('sku', 60)->nullable();
            $table->string('type', 20)->default('physical');       // ProductType: physical|service
            $table->string('short_description', 300)->nullable();  // hiển thị trong picker + product-box compact
            $table->text('description')->nullable();                // mô tả đầy đủ, dự phòng trang chi tiết tương lai
            $table->decimal('price', 12, 2)->nullable();
            $table->string('price_label', 100)->nullable();         // override hiển thị: "Liên hệ báo giá", "Từ 590.000đ"
            $table->char('currency', 3)->default('VND');
            $table->string('cover_image_url', 500)->nullable();
            $table->string('status', 20)->default('active');        // ProductStatus: active|inactive|discontinued|out_of_stock

            // 4 link affiliate cố định — cấu hình 1 lần ở đây, Post chỉ "gọi ra" chứ không nhập lại URL cho từng vị trí chèn
            $table->string('shopee_url', 500)->nullable();
            $table->string('tiktok_url', 500)->nullable();
            $table->string('supplier_url', 500)->nullable();          // link sản phẩm tại NCC (nếu NCC có trang sản phẩm riêng)
            $table->string('supplier_homepage_url', 500)->nullable(); // fallback: trang chủ/fanpage NCC khi không có link sản phẩm riêng

            $table->string('source_ref_type', 100)->nullable();     // soft-link tuỳ chọn, vd Modules\OcopRubric\Models\OcopProduct
            $table->unsignedBigInteger('source_ref_id')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedBigInteger('view_count')->default(0);
            $table->unsignedBigInteger('total_cta_click_count')->default(0);  // rollup — xem docs §9
            $table->unsignedInteger('used_in_articles_count')->default(0);    // rollup — số bài viết (DISTINCT) đang tham chiếu
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'slug'], 'uq_product_org_slug');
            $table->index(['organization_id', 'sku'], 'idx_product_org_sku');
            $table->index(['organization_id', 'category_id', 'status'], 'idx_product_org_cat_status');
            $table->index(['organization_id', 'status', 'is_featured', 'sort_order'], 'idx_product_org_status_featured');
            $table->index(['organization_id', 'name'], 'idx_product_org_name');   // hỗ trợ LIKE 'query%' cho picker
            $table->index(['source_ref_type', 'source_ref_id'], 'idx_product_source_ref');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

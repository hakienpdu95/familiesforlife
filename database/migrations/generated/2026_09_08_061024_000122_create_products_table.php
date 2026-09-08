<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            return;
        }

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('name', 250);
            $table->string('slug', 270);
            $table->string('sku', 60)->nullable();
            $table->string('type', 20)->default('physical');
            $table->string('short_description', 300)->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('price_label', 100)->nullable();
            $table->char('currency', 3)->default('VND');
            $table->string('cover_image_url', 500)->nullable();
            $table->string('status', 20)->default('active');
            $table->string('shopee_url', 500)->nullable();
            $table->string('tiktok_url', 500)->nullable();
            $table->string('supplier_url', 500)->nullable();
            $table->string('supplier_homepage_url', 500)->nullable();
            $table->string('source_ref_type', 100)->nullable();
            $table->unsignedBigInteger('source_ref_id')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedBigInteger('view_count')->default(0);
            $table->unsignedBigInteger('total_cta_click_count')->default(0);
            $table->unsignedInteger('used_in_articles_count')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->unique(['organization_id', 'slug'], 'uq_product_org_slug');
            $table->index(['organization_id', 'sku'], 'idx_product_org_sku');
            $table->index(['organization_id', 'category_id', 'status'], 'idx_product_org_cat_status');
            $table->index(['organization_id', 'status', 'is_featured', 'sort_order'], 'idx_product_org_status_featured');
            $table->index(['organization_id', 'name'], 'idx_product_org_name');
            $table->index(['source_ref_type', 'source_ref_id'], 'idx_product_source_ref');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
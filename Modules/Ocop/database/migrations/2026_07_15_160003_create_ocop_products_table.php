<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** spec/Province_Showcase_Technical_Specification.md §3.4 — ERD ocop_products. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ocop_products', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('category_id')->constrained('ocop_categories')->restrictOnDelete();
            $table->string('name', 150);
            $table->string('slug', 180)->unique();
            $table->unsignedTinyInteger('star_rating'); // validate 3–5 ở Data/Action, không CHECK constraint (SQLite dev)
            $table->text('description')->nullable();
            $table->char('province_code', 2)->nullable();
            $table->string('province_name', 255)->nullable();
            $table->char('ward_code', 5)->nullable();
            $table->string('ward_name', 255)->nullable();
            $table->string('producer_name', 150)->nullable();
            $table->string('producer_address', 255)->nullable();
            $table->string('image_path', 255)->nullable();
            $table->unsignedInteger('image_width')->nullable();
            $table->unsignedInteger('image_height')->nullable();
            $table->unsignedInteger('image_size_bytes')->nullable();
            $table->string('purchase_url', 500)->nullable();
            $table->string('status', 20)->default('draft'); // OcopProductStatus: draft|published
            $table->boolean('is_featured')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'province_code'], 'idx_ocop_status_province');
            $table->index(['category_id', 'status'], 'idx_ocop_category_status');
            $table->index(['status', 'is_featured'], 'idx_ocop_status_featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ocop_products');
    }
};

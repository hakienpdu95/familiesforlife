<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('event_categories')) {
            return;
        }

        Schema::create('event_categories', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('parent_id')->nullable()->constrained('event_categories')->nullOnDelete()->comment('Danh mục cha (cây không giới hạn cấp)');
            $table->string('name', 100)->comment('Tên danh mục');
            $table->string('slug', 120)->unique()->comment('Slug URL');
            $table->string('icon', 50)->nullable()->comment('Icon hiển thị filter chip');
            $table->char('color_hex', 7)->nullable()->comment('Màu chip/badge');
            $table->unsignedSmallInteger('sort_order')->default(0)->comment('Thứ tự sắp xếp');
            $table->boolean('is_active')->default(true)->comment('Đang hoạt động');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete()->comment('Người tạo');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->comment('Người sửa cuối');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['parent_id', 'sort_order'], 'idx_event_cat_sort');
            $table->index(['is_active', 'sort_order'], 'idx_event_cat_active');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('event_categories');
    }
};

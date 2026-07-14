<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/Menu_Navigation_Technical_Specification.md §3.2 — cây menu tự quản lý, decoupled khỏi
 * post_categories (category_id chỉ là 1 trong 3 kiểu đích khả dụng, xem MenuLinkType).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('location', 20)->default('header'); // 'header' | 'footer'
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $table->unsignedTinyInteger('depth')->default(0); // 0=cấp1, 1=cấp2, 2=cấp3 — cache lại, không tính đệ quy mỗi lần

            $table->string('label', 150);
            $table->string('icon', 80)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('open_in_new_tab')->default(false);

            $table->string('link_type', 10)->default('none'); // MenuLinkType enum
            $table->foreignId('category_id')->nullable()->constrained('post_categories')->nullOnDelete();
            $table->string('url', 2048)->nullable();

            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['location', 'parent_id', 'sort_order'], 'idx_menu_item_tree');
            $table->index(['location', 'is_active'], 'idx_menu_item_active');
            $table->index(['location', 'depth', 'sort_order'], 'idx_menu_item_depth');
            $table->index(['category_id', 'location'], 'idx_menu_item_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};

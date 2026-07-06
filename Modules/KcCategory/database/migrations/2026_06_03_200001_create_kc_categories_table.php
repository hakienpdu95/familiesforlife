<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kc_categories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('kc_categories')->restrictOnDelete();
            $table->string('name', 150);
            $table->string('slug', 160);
            $table->text('description')->nullable();
            $table->string('icon', 80)->nullable();
            $table->char('color_hex', 7)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'slug'], 'uq_kc_cat_org_slug');
            $table->index(['organization_id', 'parent_id', 'sort_order'], 'idx_kc_cat_sort');
            $table->index(['organization_id', 'is_active'], 'idx_kc_cat_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kc_categories');
    }
};

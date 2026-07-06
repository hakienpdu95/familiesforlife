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
        if (Schema::hasTable('kc_items')) {
            return;
        }

        Schema::create('kc_items', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('category_id')->constrained('kc_categories')->restrictOnDelete();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('title', 300);
            $table->string('slug', 320);
            $table->text('summary')->nullable();
            $table->longText('content')->nullable();
            $table->enum('type', ['document', 'sop', 'video', 'form', 'faq', 'case_study', 'policy'])->index();
            $table->enum('status', ['draft', 'pending_review', 'approved', 'rejected', 'archived'])->default('draft')->index();
            $table->enum('visibility', ['public', 'internal', 'restricted', 'private'])->default('internal');
            $table->char('language', 5)->nullable()->default('vi');
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('download_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('effective_date')->nullable();
            $table->timestamp('expired_date')->nullable()->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->unique(['organization_id', 'slug'], 'uq_kc_item_org_slug');
            $table->index(['organization_id', 'category_id'], 'idx_kc_item_org_cat');
            $table->index(['organization_id', 'status', 'is_featured'], 'idx_kc_item_homepage');
            $table->index(['organization_id', 'expired_date', 'status'], 'idx_kc_item_expiry');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('kc_items');
    }
};
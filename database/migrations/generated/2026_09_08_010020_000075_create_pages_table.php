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
        if (Schema::hasTable('pages')) {
            return;
        }

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('slug', 160)->unique();
            $table->string('title', 200);
            $table->string('template', 60)->default('default');
            $table->longText('content')->nullable();
            $table->string('excerpt', 500)->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_system')->default(false);
            $table->string('seo_title', 200)->nullable();
            $table->string('seo_description', 300)->nullable();
            $table->boolean('seo_noindex')->default(false);
            $table->unsignedBigInteger('view_count')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index(['status', 'published_at'], 'idx_page_status_published');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
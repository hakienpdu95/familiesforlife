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
        if (Schema::hasTable('banners')) {
            return;
        }

        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->string('placement', 60);
            $table->string('target_type', 30)->nullable();
            $table->string('target_value', 255)->nullable();
            $table->string('title', 150)->nullable();
            $table->string('image_path', 255);
            $table->unsignedInteger('image_width')->nullable();
            $table->unsignedInteger('image_height')->nullable();
            $table->unsignedInteger('image_size_bytes')->nullable();
            $table->string('alt_text', 255)->nullable();
            $table->string('link_url', 2048)->nullable();
            $table->boolean('open_in_new_tab')->default(false);
            $table->string('badge_label', 40)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('click_count')->default(0);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index(['placement', 'is_active', 'sort_order'], 'idx_banner_placement_active');
            $table->index(['placement', 'start_date', 'end_date'], 'idx_banner_placement_schedule');
            $table->index(['placement', 'target_type', 'target_value'], 'idx_banner_targeting');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
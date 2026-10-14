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
        if (Schema::hasTable('video_series_prompts')) {
            return;
        }

        Schema::create('video_series_prompts', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('post_category_id')->nullable()->constrained('post_categories')->nullOnDelete();
            $table->string('label', 150);
            $table->string('series_topic', 255);
            $table->string('pov', 500)->nullable();
            $table->text('business_goal')->nullable();
            $table->unsignedTinyInteger('episode_count')->default(5);
            $table->string('platform', 20)->default('short_form');
            $table->text('rendered_prompt');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            

            // Indexes
            $table->index('post_category_id');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('video_series_prompts');
    }
};
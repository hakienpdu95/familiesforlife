<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Công cụ nội bộ đội content, KHÔNG organization_id, KHÔNG TenantAwareModel — cùng nhóm
// generated_prompts (PromptFrameworkStudio)/content_outlines: chỉ LƯU LẠI văn bản prompt đã ghép,
// KHÔNG gọi AI Provider trong app.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_series_prompts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // route key

            $table->foreignId('post_category_id')->nullable()->constrained('post_categories')->nullOnDelete();

            $table->string('label', 150);
            $table->string('series_topic', 255);
            $table->string('pov', 500)->nullable();
            $table->text('business_goal')->nullable();
            $table->unsignedTinyInteger('episode_count')->default(5);
            $table->string('platform', 20)->default('short_form'); // key của video_series_prompt_studio.platform.options

            $table->text('rendered_prompt');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('post_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_series_prompts');
    }
};

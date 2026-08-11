<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_video_studio_shots')) {
            return;
        }

        Schema::create('ai_video_studio_shots', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('project_id')->constrained('ai_video_studio_projects')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('label', 200)->nullable();
            $table->text('subject')->nullable();
            $table->text('action')->nullable();
            $table->text('environment')->nullable();
            $table->text('camera')->nullable();
            $table->text('style')->nullable();
            $table->text('constraints')->nullable();
            $table->text('script_line')->nullable();
            $table->longText('compiled_prompt')->nullable();
            $table->longText('ai_result')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Indexes
            $table->index(['project_id', 'sort_order']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('ai_video_studio_shots');
    }
};

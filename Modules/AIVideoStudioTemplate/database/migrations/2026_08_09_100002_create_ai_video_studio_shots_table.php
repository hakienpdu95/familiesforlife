<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// spec/AIVideoStudioTemplate_Technical_Specification.md §2.2 — 1 Project có nhiều Shot (row), mỗi
// Shot = 6 field Director Prompt Template (neolemon) + script_line (lời thoại, bổ sung riêng module này).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_video_studio_shots', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

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

            $table->longText('compiled_prompt')->nullable(); // BuildShotPromptAction — ghi đè khi sửa, không versioning (§0)
            $table->longText('ai_result')->nullable(); // dán link/text kết quả từ AI ngoài, KHÔNG upload file (§0)

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_video_studio_shots');
    }
};

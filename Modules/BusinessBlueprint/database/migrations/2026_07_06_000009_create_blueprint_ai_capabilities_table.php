<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blueprint_ai_capabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blueprint_version_id')->constrained('blueprint_versions')->cascadeOnDelete();
            $table->foreignId('checklist_id')->nullable()->constrained('blueprint_checklists')->nullOnDelete();
            $table->string('capability_code', 100); // 'ocr' | 'document_validation' | 'summary' | 'recommendation' | 'scoring'...
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('ai_agent_id')->nullable();  // FK mềm → Modules\AiCopilot\Models\AiAgent
            $table->unsignedBigInteger('ai_prompt_id')->nullable(); // FK mềm → Modules\AiCopilot\Models\AiPrompt
            $table->string('trigger_event', 100)->nullable(); // 'on_checklist_upload' | 'on_checklist_complete' ...
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blueprint_ai_capabilities');
    }
};

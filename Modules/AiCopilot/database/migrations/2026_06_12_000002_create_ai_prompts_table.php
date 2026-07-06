<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_prompts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('agent_id')->constrained('ai_agents')->cascadeOnDelete();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->longText('system_prompt');
            $table->longText('user_template');
            $table->json('variables_schema')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->tinyInteger('version')->unsigned()->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['agent_id', 'is_default'], 'idx_prompts_agent_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_prompts');
    }
};

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
        if (Schema::hasTable('ai_prompts')) {
            return;
        }

        Schema::create('ai_prompts', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('agent_id')->constrained('ai_agents')->cascadeOnDelete();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->longText('system_prompt');
            $table->longText('user_template');
            $table->json('variables_schema')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('version')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index(['agent_id', 'is_default'], 'idx_prompts_agent_default');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_prompts');
    }
};
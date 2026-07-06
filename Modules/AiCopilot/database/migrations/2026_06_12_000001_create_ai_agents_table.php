<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_agents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->string('name', 120);
            $table->string('slug', 80);
            $table->text('description')->nullable();
            $table->string('task_type', 60);
            $table->string('provider', 30)->default('claude');
            $table->string('model', 80);
            $table->decimal('temperature', 3, 2)->default(0.70);
            $table->smallInteger('max_tokens')->unsigned()->default(1024);
            $table->tinyInteger('timeout_seconds')->unsigned()->default(30);
            $table->boolean('sync_mode')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'slug'], 'idx_agents_org_slug');
            $table->index('task_type', 'idx_agents_task_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_agents');
    }
};

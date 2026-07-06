<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained('organizations');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('agent_id')->constrained('ai_agents');
            $table->foreignId('prompt_id')->nullable()->constrained('ai_prompts')->nullOnDelete();

            // Polymorphic subject
            $table->string('subject_type', 150)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();

            // Request content
            $table->longText('rendered_prompt')->nullable();
            $table->json('input_variables')->nullable();

            // Response
            $table->longText('ai_output')->nullable();
            $table->string('finish_reason', 30)->nullable();

            // Metrics
            $table->string('provider', 30);
            $table->string('model', 80);
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->decimal('cost_usd', 10, 6)->default(0);
            $table->unsignedInteger('duration_ms')->nullable();

            // Lifecycle
            $table->enum('status', ['pending', 'processing', 'done', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
            // No softDeletes — immutable audit trail

            $table->index(['organization_id', 'created_at'], 'idx_req_org_created');
            $table->index('user_id', 'idx_req_user');
            $table->index('agent_id', 'idx_req_agent');
            $table->index(['subject_type', 'subject_id'], 'idx_req_subject');
            $table->index('status', 'idx_req_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_requests');
    }
};

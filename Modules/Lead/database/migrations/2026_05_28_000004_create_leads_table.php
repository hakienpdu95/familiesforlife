<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('organization_id');

            // Contact FK + snapshot (3 fields cho list view không cần JOIN)
            $table->unsignedBigInteger('contact_id');
            $table->string('contact_name', 191);
            $table->string('contact_phone', 32)->nullable();
            $table->string('contact_company', 191)->nullable();

            // Pipeline
            $table->unsignedSmallInteger('stage_id');
            $table->dateTime('stage_changed_at')->nullable();

            // Source
            $table->unsignedSmallInteger('source_id')->nullable();
            $table->string('source_detail', 191)->nullable();

            // Assignee
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->dateTime('assigned_at')->nullable();

            // Value & timeline
            $table->decimal('expected_value', 15, 2)->nullable();
            $table->char('currency', 3)->default('VND');
            $table->date('expected_close_date')->nullable();
            $table->date('actual_close_date')->nullable();
            $table->decimal('actual_value', 15, 2)->nullable();

            // Content
            $table->string('title', 255)->nullable();
            $table->text('description')->nullable();

            // Survey integration
            $table->unsignedBigInteger('survey_response_id')->nullable();
            $table->string('survey_band_code', 64)->nullable();
            $table->decimal('survey_score', 5, 2)->nullable();

            // Scoring
            $table->unsignedTinyInteger('lead_score')->default(0);
            $table->dateTime('score_updated_at')->nullable();

            // Status: 1=active 2=converted 3=archived 4=on_hold
            $table->unsignedTinyInteger('status')->default(1);

            // Activity tracking (counter cache)
            $table->dateTime('last_activity_at')->nullable();
            $table->unsignedInteger('activity_count')->default(0);

            // Idempotency — UNIQUE khi NOT NULL (MySQL supports nullable unique)
            $table->char('idempotent_key', 32)->nullable()->unique('uq_lead_idempotent');

            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Covering indexes cho hot-path queries
            $table->index(['organization_id', 'status', 'stage_id', 'updated_at'], 'idx_lead_list_view');
            $table->index(['organization_id', 'stage_id', 'status', 'lead_score'], 'idx_lead_kanban');
            $table->index(['assigned_to', 'organization_id', 'status', 'stage_id'], 'idx_lead_my_leads');
            $table->index(['organization_id', 'expected_close_date', 'status'], 'idx_lead_closing_soon');
            $table->index(['organization_id', 'last_activity_at', 'status'], 'idx_lead_stale');
            $table->index(['organization_id', 'lead_score', 'status'], 'idx_lead_hot');
            $table->index(['organization_id', 'source_id', 'created_at'], 'idx_lead_source');
            $table->index('survey_response_id', 'idx_lead_survey');
            $table->index('contact_id', 'idx_lead_contact');
            $table->index(['organization_id', 'expected_value', 'status'], 'idx_lead_value');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};

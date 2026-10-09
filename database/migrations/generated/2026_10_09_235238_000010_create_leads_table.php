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
        if (Schema::hasTable('leads')) {
            return;
        }

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedInteger('organization_id');
            $table->unsignedBigInteger('contact_id');
            $table->string('contact_name', 191);
            $table->string('contact_phone', 32)->nullable();
            $table->string('contact_company', 191)->nullable();
            $table->unsignedSmallInteger('stage_id');
            $table->dateTime('stage_changed_at')->nullable();
            $table->unsignedSmallInteger('source_id')->nullable();
            $table->string('source_detail', 191)->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->dateTime('assigned_at')->nullable();
            $table->decimal('expected_value', 15, 2)->nullable();
            $table->char('currency', 3)->default('VND');
            $table->date('expected_close_date')->nullable();
            $table->date('actual_close_date')->nullable();
            $table->decimal('actual_value', 15, 2)->nullable();
            $table->string('title', 255)->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('survey_response_id')->nullable();
            $table->string('survey_band_code', 64)->nullable();
            $table->decimal('survey_score', 5, 2)->nullable();
            $table->unsignedTinyInteger('lead_score')->default(0);
            $table->dateTime('score_updated_at')->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->dateTime('last_activity_at')->nullable();
            $table->unsignedInteger('activity_count')->default(0);
            $table->char('idempotent_key', 32)->nullable()->unique();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
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
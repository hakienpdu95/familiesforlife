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
        if (Schema::hasTable('survey_responses')) {
            return;
        }

        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('survey_id')->constrained('surveys')->cascadeOnDelete()->comment('FK -> surveys');
            $table->string('respondent_ref', 190)->nullable()->index()->comment('Email/phone match CRM');
            $table->binary('respondent_ip', 16)->nullable()->comment('Binary IP — INET6_ATON (16 bytes)');
            $table->unsignedTinyInteger('status')->default(0)->comment('0=partial 1=complete');
            $table->timestamp('submitted_at')->nullable()->comment('Thời điểm nộp hoàn tất');
            $table->timestamps();
            $table->softDeletes();
            

            // Indexes
            $table->index(['survey_id', 'status', 'submitted_at']);
            $table->index(['survey_id', 'deleted_at', 'submitted_at']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
    }
};
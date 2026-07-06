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
        if (Schema::hasTable('rc_interview_evaluations')) {
            return;
        }

        Schema::create('rc_interview_evaluations', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->unsignedBigInteger('interview_id')->index();
            $table->unsignedBigInteger('evaluator_id');
            $table->smallInteger('overall_score');
            $table->text('strengths')->nullable();
            $table->text('weaknesses')->nullable();
            $table->text('recommendation')->nullable();
            $table->string('verdict', 20);
            $table->boolean('is_submitted')->default(false);
            $table->timestamp('submitted_at')->nullable();
            

            // Indexes
            $table->unique(['interview_id', 'evaluator_id'], 'idx_rc_eval_unique');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('rc_interview_evaluations');
    }
};
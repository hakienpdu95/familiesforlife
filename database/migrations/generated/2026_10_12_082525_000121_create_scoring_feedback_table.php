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
        if (Schema::hasTable('scoring_feedback')) {
            return;
        }

        Schema::create('scoring_feedback', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('result_id')->constrained('assessment_results')->cascadeOnDelete();
            $table->string('assessment_code', 50);
            $table->string('predicted_band', 50)->nullable();
            $table->string('actual_band', 50)->nullable();
            $table->decimal('predicted_score', 5, 2)->nullable();
            $table->decimal('actual_score', 5, 2)->nullable();
            $table->string('feedback_source', 30)->comment('admin_review | observed_outcome | user_self_report');
            $table->boolean('is_processed')->default(false);
            $table->timestamps();
            

            // Indexes
            $table->index(['assessment_code', 'is_processed']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('scoring_feedback');
    }
};
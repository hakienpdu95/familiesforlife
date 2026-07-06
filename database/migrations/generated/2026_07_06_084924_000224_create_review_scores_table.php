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
        if (Schema::hasTable('review_scores')) {
            return;
        }

        Schema::create('review_scores', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('review_id')->constrained('performance_reviews')->cascadeOnDelete();
            $table->string('criteria_key', 100);
            $table->string('criteria_name');
            $table->decimal('score', 5, 2);
            $table->decimal('weight', 5, 2);
            $table->unsignedTinyInteger('max_score');
            $table->text('comment')->nullable();
            $table->timestamps();
            

            // Indexes
            $table->unique(['review_id', 'criteria_key'], 'uq_score_criteria');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('review_scores');
    }
};
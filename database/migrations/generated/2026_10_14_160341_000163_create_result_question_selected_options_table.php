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
        if (Schema::hasTable('result_question_selected_options')) {
            return;
        }

        Schema::create('result_question_selected_options', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('question_score_id')->constrained('result_question_scores')->cascadeOnDelete();
            $table->string('option_key', 128);
            

            // Indexes
            $table->index('question_score_id');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('result_question_selected_options');
    }
};
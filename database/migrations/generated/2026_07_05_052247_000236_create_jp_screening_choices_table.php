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
        if (Schema::hasTable('jp_screening_choices')) {
            return;
        }

        Schema::create('jp_screening_choices', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('question_id')->constrained('jp_screening_questions')->cascadeOnDelete();
            $table->string('choice_text', 200);
            $table->boolean('is_disqualifying')->default(false);
            $table->smallInteger('sort_order')->default(0);
            

            // Indexes
            $table->index(['question_id', 'sort_order'], 'idx_jp_choice_question');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('jp_screening_choices');
    }
};
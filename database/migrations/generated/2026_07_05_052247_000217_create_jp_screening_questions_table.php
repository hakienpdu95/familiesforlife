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
        if (Schema::hasTable('jp_screening_questions')) {
            return;
        }

        Schema::create('jp_screening_questions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('job_post_id')->constrained('jp_job_posts')->cascadeOnDelete();
            $table->string('question_text', 500);
            $table->string('question_type', 20)->default('yes_no');
            $table->boolean('is_required')->default(true);
            $table->boolean('is_disqualifying')->default(false);
            $table->string('disqualify_if_answer', 100)->nullable();
            $table->string('placeholder', 200)->nullable();
            $table->integer('max_length')->nullable();
            $table->smallInteger('sort_order')->default(0);
            

            // Indexes
            $table->index(['job_post_id', 'sort_order'], 'idx_jp_sq_post');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('jp_screening_questions');
    }
};
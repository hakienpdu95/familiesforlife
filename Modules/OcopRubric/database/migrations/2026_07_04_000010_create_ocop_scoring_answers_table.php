<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ocop_scoring_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('ocop_scoring_sessions')->cascadeOnDelete();
            $table->foreignId('criterion_id')->constrained('ocop_rubric_criteria')->restrictOnDelete();
            $table->foreignId('option_id')->nullable()->constrained('ocop_rubric_options')->nullOnDelete();
            $table->decimal('points_awarded', 5, 2)->default(0);
            $table->boolean('needs_review')->default(false);   // true = câu trả lời do map chéo version, chưa người dùng xác nhận lại (§8.4.2)
            $table->text('evidence_note')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->unique(['session_id', 'criterion_id']);
            $table->index('option_id');
            $table->index(['session_id', 'needs_review']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ocop_scoring_answers');
    }
};

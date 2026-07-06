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
        if (Schema::hasTable('ocop_scoring_answers')) {
            return;
        }

        Schema::create('ocop_scoring_answers', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('session_id')->constrained('ocop_scoring_sessions')->cascadeOnDelete();
            $table->foreignId('criterion_id')->constrained('ocop_rubric_criteria')->restrictOnDelete();
            $table->foreignId('option_id')->nullable()->constrained('ocop_rubric_options')->nullOnDelete();
            $table->decimal('points_awarded', 5, 2)->default(0);
            $table->boolean('needs_review')->default(false);
            $table->text('evidence_note')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();
            

            // Indexes
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
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('review_templates')->cascadeOnDelete();
            $table->string('criteria_key', 100);
            $table->string('criteria_name');
            $table->decimal('weight', 5, 2);
            $table->unsignedTinyInteger('max_score');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['template_id', 'criteria_key'], 'uq_criteria_key');
            $table->index(['template_id', 'sort_order'], 'idx_criteria_template');
        });

        DB::statement('ALTER TABLE review_criteria ADD CONSTRAINT chk_rc_weight CHECK (weight > 0 AND weight <= 100)');
        DB::statement('ALTER TABLE review_criteria ADD CONSTRAINT chk_rc_max_score CHECK (max_score >= 1 AND max_score <= 10)');
    }

    public function down(): void
    {
        Schema::dropIfExists('review_criteria');
    }
};

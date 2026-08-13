<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/Entity_Comparison_Module_Technical_Spec.md §3.5 — multi_select: nhiều option cho cùng 1
 * (entity, criterion) → 1 header row rỗng ở criterion_values (mọi cột value_* NULL, chỉ tồn tại
 * làm điểm neo FK) + N hàng con ở đây.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('criterion_value_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('criterion_value_id')->constrained('criterion_values')->cascadeOnDelete();
            $table->foreignId('option_id')->constrained('criterion_options')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['criterion_value_id', 'option_id'], 'uq_criterion_value_options');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('criterion_value_options');
    }
};

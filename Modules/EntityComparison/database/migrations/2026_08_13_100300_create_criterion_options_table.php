<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/Entity_Comparison_Module_Technical_Spec.md §3.4/§0 mục 8 — options cho type select|
 * multi_select. Hàng thật, KHÔNG phải JSON — giúp validate FK, filter theo option, tránh parse
 * JSON mỗi lần render (đúng mẫu Modules\Survey\Models\SurveyFieldOption).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('criterion_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('criterion_id')->constrained('criteria')->cascadeOnDelete();
            $table->string('value', 100);
            $table->string('label', 150);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['criterion_id', 'value'], 'uq_criterion_options_value');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('criterion_options');
    }
};

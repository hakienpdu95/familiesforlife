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
        if (Schema::hasTable('ocop_rubric_criteria')) {
            return;
        }

        Schema::create('ocop_rubric_criteria', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('rubric_section_id')->constrained('ocop_rubric_sections')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('ocop_rubric_criteria')->cascadeOnDelete();
            $table->string('path', 255)->default('/');
            $table->unsignedTinyInteger('depth')->default(0);
            $table->string('code', 20);
            $table->string('label', 500);
            $table->decimal('max_score', 5, 2);
            $table->text('requirement_note')->nullable();
            $table->boolean('is_scorable')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            

            // Indexes
            $table->unique(['rubric_section_id', 'code']);
            $table->index(['rubric_section_id', 'path']);
            $table->index(['parent_id', 'sort_order']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('ocop_rubric_criteria');
    }
};
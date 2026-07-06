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
        if (Schema::hasTable('review_criteria')) {
            return;
        }

        Schema::create('review_criteria', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('template_id')->constrained('review_templates')->cascadeOnDelete();
            $table->string('criteria_key', 100);
            $table->string('criteria_name');
            $table->decimal('weight', 5, 2);
            $table->unsignedTinyInteger('max_score');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
            

            // Indexes
            $table->unique(['template_id', 'criteria_key'], 'uq_criteria_key');
            $table->index(['template_id', 'sort_order'], 'idx_criteria_template');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('review_criteria');
    }
};
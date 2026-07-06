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
        if (Schema::hasTable('ocop_rubric_options')) {
            return;
        }

        Schema::create('ocop_rubric_options', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('criterion_id')->constrained('ocop_rubric_criteria')->cascadeOnDelete();
            $table->string('label', 1000);
            $table->decimal('points', 5, 2);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            

            // Indexes
            $table->index(['criterion_id', 'sort_order']);
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('ocop_rubric_options');
    }
};
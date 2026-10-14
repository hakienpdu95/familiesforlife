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
        if (Schema::hasTable('criterion_value_options')) {
            return;
        }

        Schema::create('criterion_value_options', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('criterion_value_id')->constrained('criterion_values')->cascadeOnDelete();
            $table->foreignId('option_id')->constrained('criterion_options')->cascadeOnDelete();
            $table->timestamps();
            

            // Indexes
            $table->unique(['criterion_value_id', 'option_id'], 'uq_criterion_value_options');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('criterion_value_options');
    }
};
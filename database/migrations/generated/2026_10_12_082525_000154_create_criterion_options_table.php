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
        if (Schema::hasTable('criterion_options')) {
            return;
        }

        Schema::create('criterion_options', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('criterion_id')->constrained('criteria')->cascadeOnDelete();
            $table->string('value', 100);
            $table->string('label', 150);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            

            // Indexes
            $table->unique(['criterion_id', 'value'], 'uq_criterion_options_value');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('criterion_options');
    }
};
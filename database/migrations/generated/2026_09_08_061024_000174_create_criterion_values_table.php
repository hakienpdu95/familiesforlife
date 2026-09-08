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
        if (Schema::hasTable('criterion_values')) {
            return;
        }

        Schema::create('criterion_values', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('entity_id')->constrained('entities')->cascadeOnDelete();
            $table->foreignId('criterion_id')->constrained('criteria')->cascadeOnDelete();
            $table->string('value_string', 255)->nullable();
            $table->decimal('value_number', 18, 4)->nullable();
            $table->decimal('value_number_max', 18, 4)->nullable();
            $table->boolean('value_bool')->nullable();
            $table->date('value_date')->nullable();
            $table->foreignId('option_id')->nullable()->constrained('criterion_options')->nullOnDelete();
            $table->json('value_json')->nullable();
            $table->timestamps();
            

            // Indexes
            $table->unique(['entity_id', 'criterion_id'], 'uq_criterion_values_entity_criterion');
            $table->index('criterion_id', 'idx_criterion_values_criterion');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('criterion_values');
    }
};
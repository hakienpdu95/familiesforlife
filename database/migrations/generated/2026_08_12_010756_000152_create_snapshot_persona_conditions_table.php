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
        if (Schema::hasTable('snapshot_persona_conditions')) {
            return;
        }

        Schema::create('snapshot_persona_conditions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('snapshot_persona_id')->constrained('snapshot_personas')->cascadeOnDelete();
            $table->string('target_type', 30);
            $table->string('target_code', 100)->nullable();
            $table->string('operator', 20);
            $table->decimal('threshold_value', 8, 4)->nullable();
            $table->tinyInteger('flag_value')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            

            // Indexes
            $table->index('snapshot_persona_id');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('snapshot_persona_conditions');
    }
};
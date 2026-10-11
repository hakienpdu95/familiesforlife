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
        if (Schema::hasTable('survey_field_conditions')) {
            return;
        }

        Schema::create('survey_field_conditions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable()->unique()->comment('Public UUID — expose ra ngoài, không phải PK');
            $table->unsignedInteger('order_column')->nullable()->index()->comment('Thứ tự sắp xếp — Spatie Sortable / ORDER BY');
            $table->foreignId('field_id')->constrained('survey_fields')->cascadeOnDelete();
            $table->foreignId('depends_on_field_id')->constrained('survey_fields')->cascadeOnDelete();
            $table->string('operator');
            $table->json('trigger_value');
            $table->string('action')->default('show');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            

            // Indexes
            $table->index('field_id');
        });

        
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_field_conditions');
    }
};